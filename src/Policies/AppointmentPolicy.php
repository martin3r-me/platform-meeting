<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Appointment;

class AppointmentPolicy
{
    /**
     * Darf der User dieses Appointment sehen?
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // 1. User kann sein eigenes Appointment sehen
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // 2. Meeting-Organizer kann alle Appointments sehen
        if ($appointment->meeting && $appointment->meeting->user_id === $user->id) {
            return true;
        }
        
        // 3. User ist Meeting-Teilnehmer (participant)
        if ($appointment->meeting && $appointment->meeting->participants()->where('user_id', $user->id)->exists()) {
            return true;
        }
        
        // 4. User hat andere Appointments im gleichen Meeting
        if ($appointment->meeting) {
            $hasOtherAppointments = $appointment->meeting->appointments()
                ->where('user_id', $user->id)
                ->exists();
            
            if ($hasOtherAppointments) {
                return true;
            }
        }
        
        // 5. Team-Mitgliedschaft (Backward Compatibility)
        if ($appointment->meeting && $appointment->meeting->team_id && $user->currentTeam) {
            if ($appointment->meeting->team_id === $user->currentTeam->id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Darf der User dieses Appointment bearbeiten?
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // 1. User kann sein eigenes Appointment bearbeiten
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // 2. Meeting-Organizer kann alle Appointments bearbeiten
        if ($appointment->meeting && $appointment->meeting->user_id === $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Darf der User dieses Appointment löschen?
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // 1. User kann sein eigenes Appointment löschen
        if ($appointment->user_id === $user->id) {
            return true;
        }
        
        // 2. Meeting-Organizer kann alle Appointments löschen
        if ($appointment->meeting && $appointment->meeting->user_id === $user->id) {
            return true;
        }
        
        return false;
    }
}

