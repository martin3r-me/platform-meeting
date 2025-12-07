<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Appointment;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $now = now();
        $windowEnd = $now->copy()->addDays(45);

        $baseAppointments = Appointment::query()
            ->whereHas('meeting', function ($q) use ($team) {
                $q->where('team_id', $team->id)->where('status', '!=', 'cancelled');
            })
            ->where('start_date', '>=', $now)
            ->where('start_date', '<=', $windowEnd)
            ->with('meeting')
            ->orderBy('start_date');

        $todayAppointments = (clone $baseAppointments)
            ->whereDate('start_date', $now->toDateString())
            ->get();

        $upcomingAppointments = (clone $baseAppointments)
            ->whereDate('start_date', '>', $now->toDateString())
            ->get();

        $myAppointments = (clone $baseAppointments)
            ->whereHas('meeting.participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        return view('meetings::livewire.dashboard', [
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'myAppointments' => $myAppointments,
        ])->layout('platform::layouts.app');
    }
}

