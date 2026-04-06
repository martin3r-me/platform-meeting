<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Meetings\Models\MeetingSeries;

class ListSeriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'meetings.series.GET';
    }

    public function getDescription(): string
    {
        return 'GET /meetings/series?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Meeting-Serien auf. REST-Parameter: team_id (optional, integer) - Filter nach Team-ID. Wenn nicht angegeben, wird aktuelles Team verwendet. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Team-ID. Wenn nicht angegeben, wird aktuelles Team aus Kontext verwendet.'
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

            $query = MeetingSeries::query()
                ->where('team_id', $teamId)
                ->with(['user']);

            $this->applyStandardFilters($query, $arguments, [
                'title', 'recurrence_type', 'is_active', 'created_at'
            ]);

            $this->applyStandardSearch($query, $arguments, ['title', 'description']);

            $this->applyStandardSort($query, $arguments, [
                'title', 'recurrence_type', 'is_active', 'created_at', 'next_meeting_date'
            ], 'title', 'asc');

            $this->applyStandardPagination($query, $arguments);

            $seriesList = $query->get()->map(function ($series) {
                return [
                    'id' => $series->id,
                    'uuid' => $series->uuid,
                    'title' => $series->title,
                    'description' => $series->description,
                    'location' => $series->location,
                    'recurrence_type' => $series->recurrence_type,
                    'recurrence_pattern' => $series->getRecurrencePatternText(),
                    'start_time' => $series->start_time,
                    'end_time' => $series->end_time,
                    'is_active' => $series->is_active,
                    'next_meeting_date' => $series->next_meeting_date?->toDateString(),
                    'recurrence_end_date' => $series->recurrence_end_date?->toDateString(),
                    'meetings_count' => $series->meetings()->count(),
                    'created_at' => $series->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'series' => $seriesList,
                'count' => count($seriesList),
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Serien: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['meetings', 'series', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
