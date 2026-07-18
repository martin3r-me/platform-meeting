<?php

namespace Platform\Meetings\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Meetings\Services\MeetingPromotionService;

/**
 * Promotet ein Meeting-Inbox-Item zu einer Meeting-Instanz (Workspace).
 * find-or-create per Serie; die Instanz hängt danach an denselben Knoten wie
 * das Inbox-Item.
 */
class PromoteFromInboxTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'meetings.meetings.PROMOTE';
    }

    public function getDescription(): string
    {
        return 'POST /meetings/meetings/promote - Macht aus einem Meeting-Inbox-Item eine Meeting-Instanz (Workspace). '
            . 'Eine Instanz pro Serie (find-or-create über series_master_id). REST-Parameter: inbox_item_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'inbox_item_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Meeting-Inbox-Items (ERFORDERLICH).',
                ],
            ],
            'required' => ['inbox_item_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['inbox_item_id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'inbox_item_id ist erforderlich.');
            }
            if (! $context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $meeting = app(MeetingPromotionService::class)->promoteInboxItem((int) $arguments['inbox_item_id']);

            if (! $meeting) {
                return ToolResult::error('NOT_FOUND', 'Kein promotebares Meeting-Inbox-Item gefunden.');
            }

            $userHasAccess = $context->user->teams()->where('teams.id', $meeting->team_id)->exists();
            if (! $userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf das Team dieses Meetings.');
            }

            return ToolResult::success([
                'id' => $meeting->id,
                'uuid' => $meeting->uuid,
                'title' => $meeting->title,
                'series_master_id' => $meeting->series_master_id,
                'start_date' => $meeting->start_date?->toIso8601String(),
                'was_recurring_series' => $meeting->series_master_id !== null,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Promotion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'command',
            'tags' => ['meetings', 'inbox', 'promote'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'moderate',
            'idempotent' => true,
        ];
    }
}
