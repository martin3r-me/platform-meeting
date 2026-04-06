<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\Meeting;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        if ($meeting->user_id === $user->id) {
            return true;
        }

        if ($meeting->participants()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($meeting->team_id && $user->currentTeam && $meeting->team_id === $user->currentTeam->id) {
            return true;
        }

        return false;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $meeting->user_id === $user->id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $meeting->user_id === $user->id;
    }
}
