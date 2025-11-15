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

        // Upcoming Meetings (gefiltert über Appointments)
        $upcomingMeetings = Meeting::where('team_id', $team->id)
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
            ->take(10);

        // Today's Meetings (gefiltert über Appointments)
        $todayMeetings = Meeting::where('team_id', $team->id)
            ->whereHas('appointments', function ($query) {
                $query->whereDate('start_date', today());
            })
            ->where('status', '!=', 'cancelled')
            ->with(['appointments' => function ($query) {
                $query->orderBy('start_date');
            }])
            ->get()
            ->sortBy(function ($meeting) {
                $firstAppointment = $meeting->appointments->first();
                return $firstAppointment ? $firstAppointment->start_date : now()->addYear();
            });

        // My Meetings (where user is participant, gefiltert über Appointments)
        $myMeetings = Meeting::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
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
            ->take(10);

        return view('meetings::livewire.dashboard', [
            'upcomingMeetings' => $upcomingMeetings,
            'todayMeetings' => $todayMeetings,
            'myMeetings' => $myMeetings,
        ])->layout('platform::layouts.app');
    }
}

