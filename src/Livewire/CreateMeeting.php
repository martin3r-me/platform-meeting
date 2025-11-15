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
    public $location_type = 'manual'; // 'manual' oder 'room'
    public $selected_room_id = null;
    public $participant_ids = [];
    public $sync_to_calendar = true;
    public $rooms = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'location' => 'nullable|string|max:255',
        'location_type' => 'required|in:manual,room',
        'selected_room_id' => 'nullable|string',
        'participant_ids' => 'array',
    ];

    public function updatedStartDate($value)
    {
        $this->loadRooms();
    }

    public function updatedEndDate($value)
    {
        $this->loadRooms();
    }

    public function updatedSelectedRoomId()
    {
        if ($this->selected_room_id) {
            $room = collect($this->rooms)->firstWhere('email', $this->selected_room_id);
            if ($room) {
                $this->location = $room['name'];
            }
        }
    }

    public function loadRooms()
    {
        if (!$this->start_date || !$this->end_date) {
            $this->rooms = [];
            return;
        }

        try {
            $user = Auth::user();
            $calendarService = app(MicrosoftGraphCalendarService::class);
            
            // Format für Graph API: Y-m-d\TH:i:s
            $startDateTime = \Carbon\Carbon::parse($this->start_date)->format('Y-m-d\TH:i:s');
            $endDateTime = \Carbon\Carbon::parse($this->end_date)->format('Y-m-d\TH:i:s');
            
            $this->rooms = $calendarService->findRooms($user, $startDateTime, $endDateTime);
        } catch (\Throwable $e) {
            \Log::error('Failed to load rooms', ['error' => $e->getMessage()]);
            $this->rooms = [];
        }
    }

    public function save()
    {
        // Wenn ein Raum ausgewählt wurde, verwende dessen Name als Location
        if ($this->location_type === 'room' && $this->selected_room_id) {
            $room = collect($this->rooms)->firstWhere('email', $this->selected_room_id);
            if ($room) {
                $this->location = $room['name'];
            }
        }

        $this->validate();

        $user = Auth::user();

        // Datum-Strings in Carbon-Instanzen umwandeln
        $startDate = \Carbon\Carbon::parse($this->start_date);
        $endDate = \Carbon\Carbon::parse($this->end_date);

        $meeting = Meeting::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $startDate,
            'end_date' => $endDate,
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
            'rooms' => $this->rooms,
        ])->layout('platform::layouts.app');
    }
}

