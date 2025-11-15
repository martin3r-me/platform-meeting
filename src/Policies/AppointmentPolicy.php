<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Appointment;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        // User kann nur sein eigenes Appointment sehen
        return $appointment->user_id === $user->id;
    }
}

