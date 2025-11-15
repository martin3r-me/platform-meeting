<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;

class CreateMeeting extends Component
{
    public $title = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $location = '';
    public $participant_ids = [];
    public $sync_to_calendar = true;

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'participant_ids' => 'array',
        ]);

        $user = Auth::user();

        $meeting = Meeting::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'location' => $this->location,
            'status' => 'planned',
        ]);

        // Organizer als Participant hinzufügen
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'role' => 'organizer',
            'response_status' => 'accepted',
        ]);

        // Weitere Teilnehmer hinzufügen
        foreach ($this->participant_ids as $participantId) {
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $participantId,
                'role' => 'attendee',
                'response_status' => 'notResponded',
            ]);
        }

        // Zu Microsoft Calendar syncen
        if ($this->sync_to_calendar) {
            $calendarService = app(MicrosoftGraphCalendarService::class);
            $calendarService->createEvent($meeting);
        }

        return redirect()->route('meetings.show', $meeting);
    }

    public function render()
    {
        $user = Auth::user();
        $teamMembers = $user->currentTeam->allUsers();

        return view('meetings::livewire.create-meeting', [
            'teamMembers' => $teamMembers,
        ])->layout('platform::layouts.app');
    }
}

