<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Meetings\Models\Meeting;
use Illuminate\Support\Facades\Gate;

class GetMeetingTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'meetings.meetings.DETAIL';
    }

    public function getDescription(): string
    {
        return 'GET /meetings/meetings/{id} - Ruft Meeting-Details ab inkl. Agenda-Items, Notizen und Teilnehmer. REST-Parameter: meeting_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'meeting_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Meetings (ERFORDERLICH).'
                ],
            ],
            'required' => ['meeting_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['meeting_id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'meeting_id ist erforderlich.');
            }
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $meeting = Meeting::with(['series', 'agendaItems.assignedTo', 'notes.user', 'participants'])
                ->find((int) $arguments['meeting_id']);

            if (!$meeting) {
                return ToolResult::error('NOT_FOUND', 'Meeting nicht gefunden.');
            }

            // Prüfe Zugriff über Team-Mitgliedschaft
            $userHasAccess = $context->user->teams()->where('teams.id', $meeting->team_id)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Meeting.');
            }

            $agendaItems = $meeting->agendaItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'type' => $item->type,
                    'duration_minutes' => $item->duration_minutes,
                    'order' => $item->order,
                    'assigned_to' => $item->assignedTo ? [
                        'id' => $item->assignedTo->id,
                        'name' => $item->assignedTo->fullname ?? $item->assignedTo->name,
                    ] : null,
                ];
            })->toArray();

            $notes = $meeting->notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content' => $note->content,
                    'is_published' => $note->is_published,
                    'author' => [
                        'id' => $note->user->id,
                        'name' => $note->user->fullname ?? $note->user->name,
                    ],
                    'created_at' => $note->created_at->toIso8601String(),
                ];
            })->toArray();

            $participants = $meeting->participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'display_name' => $participant->display_name,
                    'role' => $participant->role,
                    'is_external' => $participant->isExternal(),
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $meeting->id,
                'uuid' => $meeting->uuid,
                'title' => $meeting->title,
                'description' => $meeting->description,
                'location' => $meeting->location,
                'status' => $meeting->status,
                'start_date' => $meeting->start_date?->toIso8601String(),
                'end_date' => $meeting->end_date?->toIso8601String(),
                'series' => $meeting->series ? [
                    'id' => $meeting->series->id,
                    'title' => $meeting->series->title,
                    'recurrence_type' => $meeting->series->recurrence_type,
                ] : null,
                'agenda_items' => $agendaItems,
                'notes' => $notes,
                'participants' => $participants,
                'created_at' => $meeting->created_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Meetings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['meetings', 'meeting', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
