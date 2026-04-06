<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Meetings\Models\MeetingSeries;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeriesTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'meetings.series.POST';
    }

    public function getDescription(): string
    {
        return 'POST /meetings/series - Erstellt eine neue Meeting-Serie. REST-Parameter: title (required, string) - Serientitel. recurrence_type (required, string) - Wiederholungsmuster. start_time (required, string HH:MM) - Startzeit. end_time (required, string HH:MM) - Endzeit. next_meeting_date (required, string YYYY-MM-DD) - Startdatum. description, location, recurrence_end_date (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel der Meeting-Serie (ERFORDERLICH).'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Wenn nicht angegeben, wird aktuelles Team verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Serie.'
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Optional: Ort der Meetings (z.B. Konferenzraum, Zoom-Link).'
                ],
                'recurrence_type' => [
                    'type' => 'string',
                    'description' => 'Wiederholungsmuster (ERFORDERLICH). Mögliche Werte: weekly, biweekly, monthly, quarterly, yearly.',
                    'enum' => ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly']
                ],
                'recurrence_day_of_week' => [
                    'type' => 'integer',
                    'description' => 'Optional: Wochentag (1=Mo bis 7=So). Relevant bei weekly/biweekly. Standard: 1 (Montag).'
                ],
                'recurrence_day_of_month' => [
                    'type' => 'integer',
                    'description' => 'Optional: Tag im Monat (1-28). Relevant bei monthly/quarterly/yearly. Standard: 1.'
                ],
                'start_time' => [
                    'type' => 'string',
                    'description' => 'Startzeit im Format HH:MM (ERFORDERLICH). Beispiel: "09:00".'
                ],
                'end_time' => [
                    'type' => 'string',
                    'description' => 'Endzeit im Format HH:MM (ERFORDERLICH). Beispiel: "10:00".'
                ],
                'next_meeting_date' => [
                    'type' => 'string',
                    'description' => 'Startdatum der Serie im Format YYYY-MM-DD (ERFORDERLICH).'
                ],
                'recurrence_end_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Enddatum der Serie im Format YYYY-MM-DD.'
                ],
                'generate_meetings_months' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sofort Meetings für X Monate generieren. Standard: 0 (keine sofortige Generierung).'
                ],
            ],
            'required' => ['title', 'recurrence_type', 'start_time', 'end_time', 'next_meeting_date']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['title'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Serientitel ist erforderlich.');
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
                    return ToolResult::error('TEAM_NOT_FOUND', 'Team nicht gefunden oder kein Zugriff. Nutze "core.teams.GET" um verfügbare Teams zu sehen.');
                }
            } else {
                $team = $context->team;
                if (!$team) {
                    return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext. Nutze "core.teams.GET" um verfügbare Teams zu sehen.');
                }
            }

            try {
                Gate::forUser($context->user)->authorize('create', MeetingSeries::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Meeting-Serien erstellen.');
            }

            $series = MeetingSeries::create([
                'user_id' => $context->user->id,
                'team_id' => $team->id,
                'title' => $arguments['title'],
                'description' => $arguments['description'] ?? null,
                'location' => $arguments['location'] ?? null,
                'recurrence_type' => $arguments['recurrence_type'],
                'recurrence_day_of_week' => $arguments['recurrence_day_of_week'] ?? 1,
                'recurrence_day_of_month' => $arguments['recurrence_day_of_month'] ?? 1,
                'start_time' => $arguments['start_time'],
                'end_time' => $arguments['end_time'],
                'next_meeting_date' => \Carbon\Carbon::parse($arguments['next_meeting_date']),
                'recurrence_end_date' => !empty($arguments['recurrence_end_date']) ? \Carbon\Carbon::parse($arguments['recurrence_end_date']) : null,
                'is_active' => true,
            ]);

            $generatedMeetings = [];
            $generateMonths = (int) ($arguments['generate_meetings_months'] ?? 0);
            if ($generateMonths > 0) {
                $untilDate = now()->addMonths($generateMonths);
                $generatedMeetings = $series->createMeetingsUntil($untilDate);
            }

            return ToolResult::success([
                'id' => $series->id,
                'uuid' => $series->uuid,
                'title' => $series->title,
                'recurrence_type' => $series->recurrence_type,
                'recurrence_pattern' => $series->getRecurrencePatternText(),
                'start_time' => $series->start_time,
                'end_time' => $series->end_time,
                'next_meeting_date' => $series->next_meeting_date?->toDateString(),
                'is_active' => $series->is_active,
                'generated_meetings_count' => count($generatedMeetings),
                'message' => "Serie '{$series->title}' erfolgreich erstellt." . (count($generatedMeetings) > 0 ? ' ' . count($generatedMeetings) . ' Meetings generiert.' : ''),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Serie: ' . $e->getMessage());
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
            'tags' => ['meetings', 'series', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
