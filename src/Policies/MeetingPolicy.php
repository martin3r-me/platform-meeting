<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Meeting;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        // User kann Meeting sehen wenn:
        // - Er Organizer ist
        // - Er Teilnehmer ist
        // - Er im gleichen Team ist
        return $meeting->user_id === $user->id
            || $meeting->participants()->where('user_id', $user->id)->exists()
            || $meeting->team_id === $user->currentTeam->id;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        // Nur Organizer kann Meeting bearbeiten
        return $meeting->user_id === $user->id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        // Nur Organizer kann Meeting löschen
        return $meeting->user_id === $user->id;
    }
}

