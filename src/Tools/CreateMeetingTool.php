<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateMeetingTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'meetings.meetings.POST';
    }

    public function getDescription(): string
    {
        return 'POST /meetings/meetings - Erstellt ein einzelnes Meeting. REST-Parameter: title (required), start_date (required, ISO 8601), end_date (required, ISO 8601). Optional: description, location, participant_ids (array von User-IDs).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel des Meetings (ERFORDERLICH).'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Wenn nicht angegeben, wird aktuelles Team verwendet.'
                ],
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Startdatum und -zeit im ISO 8601 Format (ERFORDERLICH). Beispiel: "2026-04-10T09:00:00".'
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'Enddatum und -zeit im ISO 8601 Format (ERFORDERLICH). Beispiel: "2026-04-10T10:00:00".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Meetings.'
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Optional: Ort des Meetings.'
                ],
                'participant_ids' => [
                    'type' => 'array',
                    'description' => 'Optional: Array von User-IDs als Teilnehmer.',
                    'items' => [
                        'type' => 'integer'
                    ]
                ],
            ],
            'required' => ['title', 'start_date', 'end_date']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['title'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Meeting-Titel ist erforderlich.');
            }
            if (empty($arguments['start_date']) || empty($arguments['end_date'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Start- und Enddatum sind erforderlich.');
            }
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $arguments['team_id'] ?? null;
            if ($teamId === 0 || $teamId === '0') {
                $teamId = null;
            }

            $team = null;
            if (!empty($teamId)) {
                $team = $context->user->teams()->find($teamId);
                if (!$team) {
                    return ToolResult::error('TEAM_NOT_FOUND', 'Team nicht gefunden oder kein Zugriff.');
                }
            } else {
                $team = $context->team;
                if (!$team) {
                    return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext.');
                }
            }

            try {
                Gate::forUser($context->user)->authorize('create', Meeting::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Meetings erstellen.');
            }

            $meeting = Meeting::create([
                'user_id' => $context->user->id,
                'team_id' => $team->id,
                'title' => $arguments['title'],
                'description' => $arguments['description'] ?? null,
                'location' => $arguments['location'] ?? null,
                'status' => 'planned',
                'start_date' => \Carbon\Carbon::parse($arguments['start_date']),
                'end_date' => \Carbon\Carbon::parse($arguments['end_date']),
            ]);

            // Organizer hinzufügen
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $context->user->id,
                'role' => 'organizer',
            ]);

            // Weitere Teilnehmer hinzufügen
            if (!empty($arguments['participant_ids']) && is_array($arguments['participant_ids'])) {
                foreach ($arguments['participant_ids'] as $userId) {
                    if ((int) $userId === $context->user->id) {
                        continue; // Organizer wurde bereits hinzugefügt
                    }
                    MeetingParticipant::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => (int) $userId,
                        'role' => 'participant',
                    ]);
                }
            }

            return ToolResult::success([
                'id' => $meeting->id,
                'uuid' => $meeting->uuid,
                'title' => $meeting->title,
                'status' => $meeting->status,
                'start_date' => $meeting->start_date->toIso8601String(),
                'end_date' => $meeting->end_date->toIso8601String(),
                'location' => $meeting->location,
                'participants_count' => $meeting->participants()->count(),
                'message' => "Meeting '{$meeting->title}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Meetings: ' . $e->getMessage());
        }
    }

    public function getDependencies(): array
    {
        return [
            'required_fields' => [],
            'dependencies' => [
                [
                    'tool_name' => 'core.teams.GET',
                    'condition' => function (array $arguments, ToolContext $context): bool {
                        return empty($arguments['team_id']) || ($arguments['team_id'] ?? null) === 0;
                    },
                    'args' => function (array $arguments, ToolContext $context): array {
                        return ['include_personal' => true];
                    },
                    'merge_result' => function (string $mainToolName, ToolResult $depResult, array $arguments): ?array {
                        $teamId = $arguments['team_id'] ?? null;
                        if ($teamId === 0 || $teamId === '0') {
                            $teamId = null;
                        }

                        if (empty($teamId) && $depResult->success) {
                            return null;
                        }

                        return $arguments;
                    }
                ]
            ]
        ];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['meetings', 'meeting', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
