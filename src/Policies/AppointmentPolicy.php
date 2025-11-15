<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Appointment;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        // User kann sein eigenes Appointment sehen
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // Team-Mitglieder können auch sehen (wenn Meeting im Team ist)
        if ($appointment->meeting && $appointment->meeting->team_id) {
            return $appointment->meeting->team->users()->where('users.id', $user->id)->exists();
        }
        
        return false;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        // User kann sein eigenes Appointment bearbeiten
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // Meeting-Organizer kann auch bearbeiten
        if ($appointment->meeting && $appointment->meeting->user_id === $user->id) {
            return true;
        }
        
        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        // User kann sein eigenes Appointment löschen
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // Meeting-Organizer kann auch löschen
        if ($appointment->meeting && $appointment->meeting->user_id === $user->id) {
            return true;
        }
        
        return false;
    }
}

