<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam ?? null;

        if (!$team) {
            return view('meetings::livewire.sidebar', [
                'meetings' => collect(),
            ]);
        }

        // Meetings des Teams
        $meetings = Meeting::where('team_id', $team->id)
            ->where('start_date', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->limit(20)
            ->get();

        return view('meetings::livewire.sidebar', [
            'meetings' => $meetings,
        ]);
    }
}

