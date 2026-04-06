<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingSeries;

class MeetingsOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'meetings.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /meetings/overview - Zeigt Übersicht über das Meetings-Modul: Konzepte, Beziehungen, verfügbare Tools und Kurzstatistik. EMPFOHLEN: Nutze dieses Tool zuerst, um die Struktur des Meetings-Moduls zu verstehen. REST-Parameter: keine.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => []
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $teamId = $context->team?->id;

            $stats = [];
            if ($teamId) {
                $stats = [
                    'series_count' => MeetingSeries::where('team_id', $teamId)->count(),
                    'active_series_count' => MeetingSeries::where('team_id', $teamId)->where('is_active', true)->count(),
                    'meetings_count' => Meeting::where('team_id', $teamId)->count(),
                    'upcoming_meetings_count' => Meeting::where('team_id', $teamId)->where('start_date', '>=', now())->count(),
                ];
            }

            return ToolResult::success([
                'module' => 'meetings',
                'description' => 'Meetings-Modul zur Verwaltung von wiederkehrenden und einmaligen Meetings mit Agenda, Notizen und Teilnehmern.',
                'concepts' => [
                    'meeting_series' => [
                        'description' => 'Wiederkehrende Meeting-Serien mit konfigurierbarem Wiederholungsmuster',
                        'recurrence_types' => ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'],
                        'features' => ['Automatische Meeting-Generierung', 'Pausieren/Aktivieren', 'Enddatum optional'],
                    ],
                    'meeting' => [
                        'description' => 'Einzelnes Meeting mit Datum, Ort, Teilnehmern, Agenda und Notizen',
                        'statuses' => ['planned', 'in_progress', 'completed', 'cancelled'],
                        'features' => ['Agenda-Items (Thema, Entscheidung, Aufgabe, Info)', 'Notizen/Protokoll', 'Teilnehmer-Verwaltung'],
                    ],
                ],
                'relationships' => [
                    'hierarchy' => 'MeetingSeries → Meetings → AgendaItems / Notes / Participants',
                    'series_to_meetings' => 'Eine Serie generiert viele Meetings basierend auf dem Wiederholungsmuster',
                    'standalone_meetings' => 'Meetings können auch ohne Serie existieren (einmalige Meetings)',
                ],
                'related_tools' => [
                    'series' => [
                        'list' => 'meetings.series.GET - Serien auflisten (mit Filter/Sort/Pagination)',
                        'create' => 'meetings.series.POST - Neue Serie erstellen',
                    ],
                    'meetings' => [
                        'list' => 'meetings.meetings.GET - Meetings auflisten (Filter: series_id, status, Datumsbereich)',
                        'create' => 'meetings.meetings.POST - Einzelnes Meeting erstellen',
                        'detail' => 'meetings.meetings.DETAIL - Meeting-Details mit Agenda, Notizen, Teilnehmern',
                    ],
                ],
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Meetings-Übersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'meetings', 'concepts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
