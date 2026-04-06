<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\MeetingSeries;

class CreateSeries extends Component
{
    public $title = '';
    public $description = '';
    public $location = '';
    public $start_time = '09:00';
    public $end_time = '10:00';
    public $recurrence_type = 'weekly';
    public $recurrence_day_of_week = 1; // Monday
    public $recurrence_day_of_month = 1;
    public $next_meeting_date = '';
    public $recurrence_end_date = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'start_time' => 'required',
        'end_time' => 'required',
        'recurrence_type' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
        'next_meeting_date' => 'required|date',
        'recurrence_end_date' => 'nullable|date|after:next_meeting_date',
    ];

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        $series = MeetingSeries::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'recurrence_type' => $this->recurrence_type,
            'recurrence_day_of_week' => $this->recurrence_day_of_week,
            'recurrence_day_of_month' => $this->recurrence_day_of_month,
            'next_meeting_date' => \Carbon\Carbon::parse($this->next_meeting_date),
            'recurrence_end_date' => $this->recurrence_end_date ? \Carbon\Carbon::parse($this->recurrence_end_date) : null,
            'is_active' => true,
        ]);

        return redirect()->route('meetings.series.show', $series);
    }

    public function render()
    {
        return view('meetings::livewire.create-series')
            ->layout('platform::layouts.app');
    }
}
