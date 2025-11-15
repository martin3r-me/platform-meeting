<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Upcoming Meetings
        $upcomingMeetings = Meeting::where('team_id', $team->id)
            ->where('start_date', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        // Today's Meetings
        $todayMeetings = Meeting::where('team_id', $team->id)
            ->whereDate('start_date', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->get();

        // My Meetings (where user is participant)
        $myMeetings = Meeting::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start_date', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        return view('meetings::livewire.dashboard', [
            'upcomingMeetings' => $upcomingMeetings,
            'todayMeetings' => $todayMeetings,
            'myMeetings' => $myMeetings,
        ]);
    }
}

