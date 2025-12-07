<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\Appointment;

class Dashboard extends Component
{
    public int $year;
    public int $month;

    public function mount(): void
    {
        $now = now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    public function previousMonth(): void
    {
        $date = \Carbon\Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
    }

    public function nextMonth(): void
    {
        $date = \Carbon\Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
    }

    public function goToToday(): void
    {
        $now = now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $startOfMonth = \Carbon\Carbon::create($this->year, $this->month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $startCalendar = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endCalendar = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $appointments = Appointment::query()
            ->whereHas('meeting', function ($q) use ($team) {
                $q->where('team_id', $team->id)->where('status', '!=', 'cancelled');
            })
            ->whereBetween('start_date', [$startCalendar, $endCalendar])
            ->with('meeting')
            ->orderBy('start_date')
            ->get();

        $eventsByDate = $appointments->groupBy(fn ($a) => $a->start_date->toDateString())
            ->map(fn ($items) => $items->map(function ($a) {
                return [
                    'title' => $a->meeting->title,
                    'time' => $a->start_date->format('H:i'),
                    'end' => $a->end_date->format('H:i'),
                    'meeting_id' => $a->meeting->id,
                    'appointment_id' => $a->id,
                    'location' => $a->meeting->location,
                ];
            }));

        $days = [];
        $cursor = $startCalendar->copy();
        $today = now()->toDateString();
        while ($cursor <= $endCalendar) {
            $dateStr = $cursor->toDateString();
            $days[] = [
                'date' => $dateStr,
                'label' => $cursor->day,
                'is_today' => $dateStr === $today,
                'is_current_month' => $cursor->month === $startOfMonth->month,
                'events' => $eventsByDate->get($dateStr, collect()),
            ];
            $cursor->addDay();
        }

        $eventList = $appointments->map(function ($a) {
            return [
                'title' => $a->meeting->title,
                'date' => $a->start_date->format('d.m.Y'),
                'time' => $a->start_date->format('H:i'),
                'end' => $a->end_date->format('H:i'),
                'meeting_id' => $a->meeting->id,
                'appointment_id' => $a->id,
                'location' => $a->meeting->location,
            ];
        });

        return view('meetings::livewire.dashboard', [
            'monthLabel' => $startOfMonth->locale('de')->isoFormat('MMMM YYYY'),
            'days' => $days,
            'eventList' => $eventList,
        ])->layout('platform::layouts.app');
    }
}

