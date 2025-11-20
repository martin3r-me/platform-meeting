<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Meeting;

class MeetingPolicy
{
    /**
     * Darf der User dieses Meeting sehen?
     */
    public function view(User $user, Meeting $meeting): bool
    {
        // 1. Organizer hat immer Zugriff
        if ($meeting->user_id === $user->id) {
            return true;
        }

        // 2. User ist explizit als Teilnehmer eingetragen
        if ($meeting->participants()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // 3. User hat Appointments im Meeting (auch wenn nicht als Teilnehmer eingetragen)
        // Das entspricht der Sidebar-Logik: Meetings mit User-Appointments werden angezeigt
        $hasAppointments = $meeting->appointments()
            ->where('user_id', $user->id)
            ->exists();

        if ($hasAppointments) {
            return true;
        }

        // 4. Team-Mitgliedschaft (Backward Compatibility)
        if ($meeting->team_id && $user->currentTeam && $meeting->team_id === $user->currentTeam->id) {
            return true;
        }

        return false;
    }

    /**
     * Darf der User dieses Meeting bearbeiten?
     */
    public function update(User $user, Meeting $meeting): bool
    {
        // Nur Organizer kann Meeting bearbeiten
        return $meeting->user_id === $user->id;
    }

    /**
     * Darf der User dieses Meeting löschen?
     */
    public function delete(User $user, Meeting $meeting): bool
    {
        // Nur Organizer kann Meeting löschen
        return $meeting->user_id === $user->id;
    }
}

