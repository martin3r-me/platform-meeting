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

        // Meetings des Teams (gefiltert über Appointments)
        $meetings = Meeting::where('team_id', $team->id)
            ->whereHas('appointments', function ($query) {
                $query->where('start_date', '>=', now());
            })
            ->where('status', '!=', 'cancelled')
            ->with(['appointments' => function ($query) {
                $query->orderBy('start_date');
            }])
            ->get()
            ->sortBy(function ($meeting) {
                $firstAppointment = $meeting->appointments->first();
                return $firstAppointment ? $firstAppointment->start_date : now()->addYear();
            })
            ->take(20);

        return view('meetings::livewire.sidebar', [
            'meetings' => $meetings,
        ]);
    }
}

