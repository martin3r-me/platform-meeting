<?php

namespace Platform\Meetings\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\Meetings\Models\Meeting;

/**
 * Promotet ein Meeting-Inbox-Item zu einer Meeting-Instanz (Workspace).
 *
 * Kern der Phase C: EINE Instanz pro Serie. Über series_master_id (Phase B) wird
 * per find-or-create genau eine Meeting-Instanz je Terminserie erzeugt; alle
 * Vorkommen docken über inbox_items.meeting_id an. Die Instanz wird an dieselben
 * Knoten gehängt wie das Inbox-Item (morph `meeting`) und trägt dort Wissen bei.
 *
 * Loose gekoppelt: liest Inbox-/Connector-Tabellen via DB::table und nutzt die
 * Organization-Bridge nur, wenn vorhanden.
 */
class MeetingPromotionService
{
    public function promoteInboxItem(int $inboxItemId): ?Meeting
    {
        if (! Schema::hasTable('inbox_items')) {
            return null;
        }

        $item = DB::table('inbox_items')->where('id', $inboxItemId)->first();
        if (! $item || $item->channel !== 'meeting') {
            return null;
        }

        $session = null;
        if (
            $item->source_type === 'user_connector_meeting_session'
            && Schema::hasTable('user_connector_meeting_sessions')
        ) {
            $session = DB::table('user_connector_meeting_sessions')
                ->where('id', $item->source_id)
                ->first();
        }

        $icalUid = $item->ical_uid ?? null;
        $seriesMasterId = $item->series_master_id ?? null;

        // find-or-create: EINE geteilte Instanz pro realem Termin. Bevorzugt über
        // iCalUId (über Beteiligte UND Vorkommen stabil) — der erste, der einklinkt,
        // legt sie an, alle weiteren docken an dieselbe. Fallback: series_master_id.
        $meeting = null;
        if ($icalUid && Schema::hasColumn('meetings_meetings', 'ical_uid')) {
            $meeting = Meeting::where('ical_uid', $icalUid)->first();
        }
        if (! $meeting && $seriesMasterId && Schema::hasColumn('meetings_meetings', 'series_master_id')) {
            $meeting = Meeting::where('series_master_id', $seriesMasterId)->first();
        }

        if (! $meeting) {
            $meeting = new Meeting();
            $meeting->team_id = $item->team_id;
            $meeting->user_id = $item->user_id;
            $meeting->series_master_id = $seriesMasterId;
            if (Schema::hasColumn('meetings_meetings', 'ical_uid')) {
                $meeting->ical_uid = $icalUid;
            }
            $meeting->title = $session->subject ?? $item->subject ?? 'Meeting';
            $meeting->description = $session->body_preview ?? null;
            $meeting->location = $session->location ?? null;
            $meeting->status = 'confirmed';
            $meeting->start_date = $session->start_at ?? $item->received_at ?? null;
            $meeting->end_date = $session->end_at ?? null;
            $meeting->save();
        }

        // Backlink: dieses Vorkommen + alle weiteren offenen Vorkommen desselben
        // Termins (gleicher Nutzer, gleiche Identität) an die Instanz binden.
        // Inbox ist user-scoped; die Sicht anderer Beteiligter folgt über
        // Mitgliedschaft (Increment 2), nicht über Fremd-Backlinks.
        DB::table('inbox_items')->where('id', $item->id)->update(['meeting_id' => $meeting->id]);

        $identityCol = $icalUid ? 'ical_uid' : ($seriesMasterId ? 'series_master_id' : null);
        $identityVal = $icalUid ?: $seriesMasterId;
        if ($identityCol) {
            DB::table('inbox_items')
                ->where('user_id', $item->user_id)
                ->where($identityCol, $identityVal)
                ->whereNull('meeting_id')
                ->update(['meeting_id' => $meeting->id]);
        }

        $this->linkMeetingToItemEntities($meeting, (int) $item->id);

        return $meeting;
    }

    /**
     * Hängt die Meeting-Instanz an dieselben Knoten wie das Inbox-Item.
     */
    protected function linkMeetingToItemEntities(Meeting $meeting, int $inboxItemId): void
    {
        $bridge = \Platform\Organization\Services\EntityDimensionBridge::class;
        if (! class_exists($bridge) || ! Schema::hasTable('organization_dimension_links')) {
            return;
        }

        $entityIds = $bridge::linksForLinkables(['inbox_item'], [$inboxItemId], false)
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->all();
        if (empty($entityIds)) {
            return;
        }

        $existing = $bridge::linksForLinkables(['meeting'], [$meeting->id], false)
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->flip();

        foreach ($entityIds as $entityId) {
            if (isset($existing[$entityId])) {
                continue;
            }
            $bridge::createLink((int) $entityId, 'meeting', $meeting->id);
        }
    }
}
