<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Meetings\Models\Meeting;

class ListMeetingsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'meetings.meetings.GET';
    }

    public function getDescription(): string
    {
        return 'GET /meetings/meetings?team_id={id}&meeting_series_id={id}&filters=[...]&search=...&sort=[...] - Listet Meetings auf. REST-Parameter: team_id (optional), meeting_series_id (optional) - Filter nach Serie. filters, search, sort, limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Team-ID. Wenn nicht angegeben, wird aktuelles Team verwendet.'
                    ],
                    'meeting_series_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Meeting-Serie-ID. Zeigt nur Meetings dieser Serie.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $arguments['team_id'] ?? null;
            if ($teamId === 0 || $teamId === '0') {
                $teamId = null;
            }

            if ($teamId === null) {
                $teamId = $context->team?->id;
            }

            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze "core.teams.GET" um verfügbare Teams zu sehen.');
            }

            $userHasAccess = $context->user->teams()->where('teams.id', $teamId)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            $query = Meeting::query()
                ->where('team_id', $teamId)
                ->with(['user', 'series']);

            if (!empty($arguments['meeting_series_id'])) {
                $query->where('meeting_series_id', (int) $arguments['meeting_series_id']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'title', 'status', 'start_date', 'end_date', 'meeting_series_id'
            ]);

            $this->applyStandardSearch($query, $arguments, ['title', 'description', 'location']);

            $this->applyStandardSort($query, $arguments, [
                'title', 'status', 'start_date', 'end_date', 'created_at'
            ], 'start_date', 'desc');

            $this->applyStandardPagination($query, $arguments);

            $meetingsList = $query->get()->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'uuid' => $meeting->uuid,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'location' => $meeting->location,
                    'status' => $meeting->status,
                    'start_date' => $meeting->start_date?->toIso8601String(),
                    'end_date' => $meeting->end_date?->toIso8601String(),
                    'meeting_series_id' => $meeting->meeting_series_id,
                    'series_title' => $meeting->series?->title,
                    'participants_count' => $meeting->participants()->count(),
                    'agenda_items_count' => $meeting->agendaItems()->count(),
                    'notes_count' => $meeting->notes()->count(),
                    'created_at' => $meeting->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'meetings' => $meetingsList,
                'count' => count($meetingsList),
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Meetings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['meetings', 'meeting', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
