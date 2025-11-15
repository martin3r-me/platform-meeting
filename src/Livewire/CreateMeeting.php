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
    public $duration_minutes = 60; // Standard: 60 Minuten
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
        // Konvertiere datetime-local Format (Y-m-d\TH:i) zu Y-m-d H:i:s
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
                // Konvertiere datetime-local Format falls nötig
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
        // Räume-Ladung vorerst deaktiviert - später implementieren
        $this->rooms = [];
        return;
        
        // Enddatum berechnen, falls noch nicht gesetzt
        if (!$this->end_date && $this->start_date && $this->duration_minutes) {
            $this->calculateEndDate();
        }

        if (!$this->start_date || !$this->end_date) {
            $this->rooms = [];
            return;
        }

        try {
            $user = Auth::user();
            $calendarService = app(MicrosoftGraphCalendarService::class);
            
            // Format für Graph API: Y-m-d\TH:i:s
            $startValue = str_replace('T', ' ', $this->start_date);
            if (!str_contains($startValue, ':')) {
                $startValue .= ' 00:00:00';
            } elseif (substr_count($startValue, ':') === 1) {
                $startValue .= ':00';
            }
            
            $startDateTime = \Carbon\Carbon::parse($startValue)->format('Y-m-d\TH:i:s');
            $endDateTime = \Carbon\Carbon::parse($this->end_date)->format('Y-m-d\TH:i:s');
            
            $this->rooms = $calendarService->findRooms($user, $startDateTime, $endDateTime);
        } catch (\Throwable $e) {
            \Log::error('Failed to load rooms', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->rooms = [];
        }
    }

    protected function prepareForValidation($attributes)
    {
        // Konvertiere datetime-local Format zu Y-m-d H:i:s
        if (!empty($this->start_date) && str_contains($this->start_date, 'T')) {
            $this->start_date = str_replace('T', ' ', $this->start_date) . ':00';
        }

        // Enddatum berechnen, falls noch nicht gesetzt
        if (!$this->end_date && $this->start_date && $this->duration_minutes) {
            $this->calculateEndDate();
        }

        return $attributes;
    }

    public function save()
    {
        // Konvertiere datetime-local Format zu Y-m-d H:i:s
        if (!empty($this->start_date) && str_contains($this->start_date, 'T')) {
            $this->start_date = str_replace('T', ' ', $this->start_date) . ':00';
        }

        // Enddatum berechnen, falls noch nicht gesetzt
        if (!$this->end_date && $this->start_date && $this->duration_minutes) {
            $this->calculateEndDate();
        }

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
        // Konvertiere falls nötig
        $startValue = str_replace('T', ' ', $this->start_date);
        if (!str_contains($startValue, ':')) {
            $startValue .= ' 00:00:00';
        } elseif (substr_count($startValue, ':') === 1) {
            $startValue .= ':00';
        }
        
        $startDate = \Carbon\Carbon::parse($startValue);
        $endDate = \Carbon\Carbon::parse($this->end_date);

        // Meeting erstellen (ohne konkrete Daten - die kommen in Appointments)
        $meeting = Meeting::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location, // Standard-Location
            'status' => 'planned',
        ]);

        // Organizer als Participant hinzufügen
        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'role' => 'organizer',
            'response_status' => 'accepted',
        ]);

        // Appointment für Organizer erstellen (mit konkreten Daten)
        \Platform\Meetings\Models\Appointment::create([
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'team_id' => $meeting->team_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->location, // Kann vom Meeting überschrieben werden
            'sync_status' => 'pending',
        ]);

        // Weitere Teilnehmer hinzufügen (Organizer überspringen)
        foreach ($this->participant_ids as $participantId) {
            // Überspringe den Organizer, da er bereits hinzugefügt wurde
            if ($participantId == $user->id) {
                continue;
            }
            
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $participantId,
                'role' => 'attendee',
                'response_status' => 'notResponded',
            ]);

            // Appointment für jeden Teilnehmer erstellen
            \Platform\Meetings\Models\Appointment::create([
                'meeting_id' => $meeting->id,
                'user_id' => $participantId,
                'team_id' => $meeting->team_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'location' => $this->location,
                'sync_status' => 'pending',
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

