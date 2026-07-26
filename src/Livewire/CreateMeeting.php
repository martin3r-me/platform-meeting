<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;

class CreateMeeting extends Component
{
    public $title = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $duration_minutes = 60;
    public $location = '';
    public $participant_ids = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'location' => 'nullable|string|max:255',
        'participant_ids' => 'array',
    ];

    public function updatedStartDate($value)
    {
        if ($value) {
            $this->start_date = str_replace('T', ' ', $value) . ':00';
        }
        $this->calculateEndDate();
    }

    public function updatedDurationMinutes($value)
    {
        $this->calculateEndDate();
    }

    public function calculateEndDate()
    {
        if ($this->start_date && $this->duration_minutes) {
            try {
                $startValue = str_replace('T', ' ', $this->start_date);
                if (!str_contains($startValue, ':')) {
                    $startValue .= ' 00:00:00';
                } elseif (substr_count($startValue, ':') === 1) {
                    $startValue .= ':00';
                }

                $start = \Carbon\Carbon::parse($startValue);
                $this->end_date = $start->copy()->addMinutes($this->duration_minutes)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                \Log::error('Failed to calculate end date', [
                    'error' => $e->getMessage(),
                    'start_date' => $this->start_date,
                    'duration_minutes' => $this->duration_minutes,
                ]);
            }
        }
    }

    protected function prepareForValidation($attributes)
    {
        if (!empty($this->start_date) && str_contains($this->start_date, 'T')) {
            $this->start_date = str_replace('T', ' ', $this->start_date) . ':00';
        }

        if (!$this->end_date && $this->start_date && $this->duration_minutes) {
            $this->calculateEndDate();
        }

        return $attributes;
    }

    public function save()
    {
        if (!empty($this->start_date) && str_contains($this->start_date, 'T')) {
            $this->start_date = str_replace('T', ' ', $this->start_date) . ':00';
        }

        if (!$this->end_date && $this->start_date && $this->duration_minutes) {
            $this->calculateEndDate();
        }

        $this->validate();

        $user = Auth::user();

        $startValue = str_replace('T', ' ', $this->start_date);
        if (!str_contains($startValue, ':')) {
            $startValue .= ' 00:00:00';
        } elseif (substr_count($startValue, ':') === 1) {
            $startValue .= ':00';
        }

        $startDate = \Carbon\Carbon::parse($startValue);
        $endDate = \Carbon\Carbon::parse($this->end_date);

        $meeting = Meeting::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'status' => 'planned',
            'visibility' => Meeting::DEFAULT_VISIBILITY,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        // Organizer als Participant
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'role' => 'organizer',
        ]);

        // Weitere Teilnehmer
        foreach ($this->participant_ids as $participantId) {
            if ($participantId == $user->id) {
                continue;
            }

            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $participantId,
                'role' => 'attendee',
            ]);
        }

        return redirect()->route('meetings.show', $meeting);
    }

    public function render()
    {
        $user = Auth::user();
        $teamMembers = $user->currentTeam->users()
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->fullname ?? $user->name,
                    'email' => $user->email,
                ];
            })
            ->values();

        return view('meetings::livewire.create-meeting', [
            'teamMembers' => $teamMembers,
        ])->layout('platform::layouts.app');
    }
}
