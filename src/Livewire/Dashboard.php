<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingSeries;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $now = now();
        $windowEnd = $now->copy()->addDays(45);

        $baseMeetings = Meeting::query()
            ->where('team_id', $team->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_date', '>=', $now)
            ->where('start_date', '<=', $windowEnd)
            ->orderBy('start_date');

        $todayMeetings = (clone $baseMeetings)
            ->whereDate('start_date', $now->toDateString())
            ->get();

        $upcomingMeetings = (clone $baseMeetings)
            ->whereDate('start_date', '>', $now->toDateString())
            ->get();

        $myMeetings = (clone $baseMeetings)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('participants', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->get();

        $series = MeetingSeries::where('team_id', $team->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('meetings::livewire.dashboard', [
            'todayMeetings' => $todayMeetings,
            'upcomingMeetings' => $upcomingMeetings,
            'myMeetings' => $myMeetings,
            'series' => $series,
        ])->layout('platform::layouts.app');
    }
}
