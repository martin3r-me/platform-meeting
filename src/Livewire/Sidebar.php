<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingSeries;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam ?? null;

        if (!$team) {
            return view('meetings::livewire.sidebar', [
                'meetings' => collect(),
                'series' => collect(),
            ]);
        }

        $meetings = Meeting::where('team_id', $team->id)
            ->where('start_date', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->take(20)
            ->get();

        $series = MeetingSeries::where('team_id', $team->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('meetings::livewire.sidebar', [
            'meetings' => $meetings,
            'series' => $series,
        ]);
    }
}
