<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting as MeetingModel;
use Platform\Meetings\Models\MeetingAgendaSlot;
use Platform\Meetings\Models\MeetingAgendaItem;
use Platform\Meetings\Models\Appointment;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class Meeting extends Component
{
    public MeetingModel $meeting;
    
    // Agenda Item Editing
    public $editingAgendaItemId = null;
    public $editingAgendaItem = [
        'title' => '',
        'description' => '',
        'duration_minutes' => null,
        'assigned_to_id' => null,
    ];
    
    // Appointment Creation
    public $showCreateAppointmentModal = false;
    public $createAppointment = [
        'user_id' => null,
        'start_date' => '',
        'end_date' => '',
    ];

    #[On('updateMeeting')]
    public function updateMeeting()
    {
        $this->mount($this->meeting);
    }

    #[On('agendaSlotUpdated')]
    public function agendaSlotUpdated()
    {
        $this->mount($this->meeting);
    }

    public function mount(MeetingModel $meeting)
    {
        $this->meeting = $meeting;
        $this->authorize('view', $this->meeting);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->meeting),
            'modelId' => $this->meeting->id,
            'subject' => $this->meeting->title,
            'description' => $this->meeting->description ?? '',
            'url' => route('meetings.show', $this->meeting),
            'source' => 'meetings.meeting.view',
            'recipients' => [],
            'meta' => [
                'start_date' => $this->meeting->start_date,
                'end_date' => $this->meeting->end_date,
            ],
        ]);

        $this->dispatch('organization', [
            'context_type' => get_class($this->meeting),
            'context_id' => $this->meeting->id,
            'allow_time_entry' => true,
            'allow_context_management' => true,
            'can_link_to_entity' => true,
        ]);
    }

    public function createAgendaSlot()
    {
        $this->authorize('update', $this->meeting);

        $maxOrder = $this->meeting->agendaSlots()->max('order') ?? 0;

        MeetingAgendaSlot::create([
            'meeting_id' => $this->meeting->id,
            'name' => 'Neue Spalte',
            'order' => $maxOrder + 1,
        ]);

        $this->dispatch('agendaSlotUpdated');
    }

    public function updateAgendaSlotOrder($slotIds)
    {
        $this->authorize('update', $this->meeting);

        foreach ($slotIds as $order => $slotId) {
            MeetingAgendaSlot::where('id', $slotId)
                ->where('meeting_id', $this->meeting->id)
                ->update(['order' => $order]);
        }

        $this->dispatch('agendaSlotUpdated');
    }

    public function updateAgendaItemOrder($itemIds)
    {
        $this->authorize('update', $this->meeting);

        foreach ($itemIds as $order => $itemId) {
            MeetingAgendaItem::where('id', $itemId)
                ->where('meeting_id', $this->meeting->id)
                ->update(['order' => $order]);
        }

        $this->dispatch('agendaSlotUpdated');
    }

    public function createAgendaItem($slotId = null)
    {
        $this->authorize('update', $this->meeting);

        $maxOrder = $this->meeting->agendaItems()->max('order') ?? 0;

        MeetingAgendaItem::create([
            'meeting_id' => $this->meeting->id,
            'agenda_slot_id' => $slotId,
            'title' => 'Neues Agenda Item',
            'order' => $maxOrder + 1,
            'status' => 'todo',
        ]);

        $this->dispatch('agendaSlotUpdated');
    }

    public function editAgendaItem($itemId)
    {
        $this->authorize('update', $this->meeting);
        
        $item = MeetingAgendaItem::findOrFail($itemId);
        $this->editingAgendaItemId = $itemId;
        $this->editingAgendaItem = [
            'title' => $item->title,
            'description' => $item->description ?? '',
            'duration_minutes' => $item->duration_minutes,
            'assigned_to_id' => $item->assigned_to_id,
        ];
    }

    public function saveAgendaItem()
    {
        $this->authorize('update', $this->meeting);
        
        $this->validate([
            'editingAgendaItem.title' => 'required|string|max:255',
            'editingAgendaItem.description' => 'nullable|string',
            'editingAgendaItem.duration_minutes' => 'nullable|integer|min:1',
            'editingAgendaItem.assigned_to_id' => 'nullable|exists:users,id',
        ]);

        $item = MeetingAgendaItem::findOrFail($this->editingAgendaItemId);
        $item->update([
            'title' => $this->editingAgendaItem['title'],
            'description' => $this->editingAgendaItem['description'],
            'duration_minutes' => $this->editingAgendaItem['duration_minutes'],
            'assigned_to_id' => $this->editingAgendaItem['assigned_to_id'],
        ]);

        $this->cancelEditAgendaItem();
        $this->dispatch('agendaSlotUpdated');
    }

    public function cancelEditAgendaItem()
    {
        $this->editingAgendaItemId = null;
        $this->editingAgendaItem = [
            'title' => '',
            'description' => '',
            'duration_minutes' => null,
            'assigned_to_id' => null,
        ];
    }

    public function deleteAgendaItem($itemId)
    {
        $this->authorize('update', $this->meeting);
        
        MeetingAgendaItem::findOrFail($itemId)->delete();
        $this->dispatch('agendaSlotUpdated');
    }

    public function openCreateAppointmentModal()
    {
        $this->authorize('update', $this->meeting);
        
        $this->createAppointment = [
            'user_id' => null,
            'start_date' => $this->meeting->start_date->format('Y-m-d\TH:i'),
            'end_date' => $this->meeting->end_date->format('Y-m-d\TH:i'),
        ];
        $this->showCreateAppointmentModal = true;
    }

    public function closeCreateAppointmentModal()
    {
        $this->showCreateAppointmentModal = false;
        $this->createAppointment = [
            'user_id' => null,
            'start_date' => '',
            'end_date' => '',
        ];
    }

    public function createAppointment()
    {
        $this->authorize('update', $this->meeting);
        
        // Konvertiere datetime-local Format falls nötig
        if (!empty($this->createAppointment['start_date']) && str_contains($this->createAppointment['start_date'], 'T')) {
            $this->createAppointment['start_date'] = str_replace('T', ' ', $this->createAppointment['start_date']) . ':00';
        }
        if (!empty($this->createAppointment['end_date']) && str_contains($this->createAppointment['end_date'], 'T')) {
            $this->createAppointment['end_date'] = str_replace('T', ' ', $this->createAppointment['end_date']) . ':00';
        }
        
        $this->validate([
            'createAppointment.user_id' => 'required|exists:users,id',
            'createAppointment.start_date' => 'required|date',
            'createAppointment.end_date' => 'required|date|after:createAppointment.start_date',
        ]);

        $startDate = \Carbon\Carbon::parse($this->createAppointment['start_date']);
        $endDate = \Carbon\Carbon::parse($this->createAppointment['end_date']);

        $appointment = Appointment::create([
            'meeting_id' => $this->meeting->id,
            'user_id' => $this->createAppointment['user_id'],
            'team_id' => $this->meeting->team_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->meeting->location, // Standard-Location vom Meeting
            'sync_status' => 'pending',
        ]);

        // Zu Microsoft Calendar syncen (wird später implementiert)
        // TODO: Eigene Methode für einzelne User-Events implementieren

        $this->closeCreateAppointmentModal();
        $this->dispatch('appointmentCreated');
    }

    #[Computed]
    public function activities()
    {
        if (!$this->meeting) {
            return collect();
        }

        return $this->meeting->activities()
            ->with('user')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                $title = $this->formatActivityTitle($activity);
                $time = $activity->created_at->diffForHumans();
                
                return [
                    'id' => $activity->id,
                    'title' => $title,
                    'time' => $time,
                    'user' => $activity->user?->name ?? 'System',
                    'type' => $activity->activity_type,
                    'name' => $activity->name,
                ];
            });
    }

    private function formatActivityTitle($activity): string
    {
        $userName = $activity->user?->name ?? 'System';
        $activityName = $activity->name;
        
        // Übersetze Activity-Namen
        $translations = [
            'created' => 'erstellt',
            'updated' => 'aktualisiert',
            'deleted' => 'gelöscht',
            'manual' => 'hat eine Nachricht hinzugefügt',
        ];
        
        $translatedName = $translations[$activityName] ?? $activityName;
        
        // Wenn es eine Nachricht gibt, zeige diese
        if ($activity->message) {
            return "{$userName}: {$activity->message}";
        }
        
        // Wenn es Änderungen gibt, zeige diese
        if ($activity->properties && !empty($activity->properties)) {
            $props = $activity->properties;
            $changedFields = [];
            
            // Prüfe ob es old/new gibt (strukturierte Properties)
            if (isset($props['old']) || isset($props['new'])) {
                if (isset($props['old']) && isset($props['new'])) {
                    $changedFields = array_keys($props['new']);
                } elseif (isset($props['new'])) {
                    $changedFields = array_keys($props['new']);
                }
            } else {
                // Direkte Properties (z.B. bei created)
                $changedFields = array_keys($props);
            }
            
            if (!empty($changedFields)) {
                $fieldNames = array_map(function($field) {
                    $translations = [
                        'title' => 'Titel',
                        'description' => 'Beschreibung',
                        'start_date' => 'Startdatum',
                        'end_date' => 'Enddatum',
                        'location' => 'Ort',
                        'status' => 'Status',
                    ];
                    return $translations[$field] ?? $field;
                }, $changedFields);
                
                return "{$userName} hat " . implode(', ', $fieldNames) . " {$translatedName}";
            }
        }
        
        return "{$userName} hat das Meeting {$translatedName}";
    }

    public function addParticipant($userId)
    {
        $this->authorize('update', $this->meeting);
        
        // Prüfen ob User bereits Teilnehmer ist
        $existingParticipant = $this->meeting->participants()->where('user_id', $userId)->first();
        if ($existingParticipant) {
            return; // User bereits Teilnehmer
        }
        
        // Neuen Teilnehmer hinzufügen
        \Platform\Meetings\Models\MeetingParticipant::create([
            'meeting_id' => $this->meeting->id,
            'user_id' => $userId,
            'role' => 'attendee',
            'response_status' => 'notResponded',
        ]);
        
        $this->meeting->refresh();
    }

    public function removeParticipant($userId)
    {
        $this->authorize('update', $this->meeting);
        
        // Organizer kann nicht entfernt werden
        if ($userId == $this->meeting->user_id) {
            return;
        }
        
        \Platform\Meetings\Models\MeetingParticipant::where('meeting_id', $this->meeting->id)
            ->where('user_id', $userId)
            ->delete();
        
        $this->meeting->refresh();
    }

    public function render()
    {
        $user = Auth::user();

        // Appointments laden
        $appointments = $this->meeting->appointments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Meeting-Participants für Appointment-Erstellung (nur Beteiligte)
        $meetingParticipants = $this->meeting->participants()
            ->with('user')
            ->get()
            ->map(function ($participant) {
                return $participant->user;
            })
            ->filter()
            ->sortBy('name')
            ->values();

        // Team-Mitglieder für Participant-Verwaltung (alle Team-Mitglieder)
        $teamMembers = $this->meeting->team->users()
            ->orderBy('name')
            ->get();

        return view('meetings::livewire.meeting', [
            'activities' => $this->activities,
            'appointments' => $appointments,
            'meetingParticipants' => $meetingParticipants,
            'teamMembers' => $teamMembers,
        ])->layout('platform::layouts.app');
    }
}

