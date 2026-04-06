<?php

namespace Platform\Meetings\Policies;

use Platform\Core\Models\User;
use Platform\Meetings\Models\MeetingSeries;

class MeetingSeriesPolicy
{
    public function view(User $user, MeetingSeries $series): bool
    {
        if ($series->team_id && $user->currentTeam && $series->team_id === $user->currentTeam->id) {
            return true;
        }

        return $series->user_id === $user->id;
    }

    public function update(User $user, MeetingSeries $series): bool
    {
        return $series->user_id === $user->id;
    }

    public function delete(User $user, MeetingSeries $series): bool
    {
        return $series->user_id === $user->id;
    }
}
