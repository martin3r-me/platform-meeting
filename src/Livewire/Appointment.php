<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Appointment as AppointmentModel;
use Platform\Meetings\Models\MeetingAgendaSlot;
use Platform\Meetings\Models\MeetingAgendaItem;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class Appointment extends Component
{
    public AppointmentModel $appointment;
    
    // Agenda Item Editing
    public $editingAgendaItemId = null;
    public $editingAgendaItem = [
        'title' => '',
        'description' => '',
        'duration_minutes' => null,
        'assigned_to_id' => null,
    ];

    #[On('updateAppointment')]
    public function updateAppointment()
    {
        $this->mount($this->appointment);
    }

    #[On('agendaSlotUpdated')]
    public function agendaSlotUpdated()
    {
        $this->mount($this->appointment);
    }

    public function mount(AppointmentModel $appointment)
    {
        $this->appointment = $appointment->load(['meeting', 'user', 'team']);
        $this->authorize('view', $this->appointment);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->appointment),
            'modelId' => $this->appointment->id,
            'subject' => $this->appointment->meeting->title,
            'description' => $this->appointment->meeting->description ?? '',
            'url' => route('meetings.appointments.show', $this->appointment),
            'source' => 'meetings.appointment.view',
            'recipients' => [],
            'meta' => [
                'start_date' => $this->appointment->meeting->start_date,
                'end_date' => $this->appointment->meeting->end_date,
            ],
        ]);

        $this->dispatch('organization', [
            'context_type' => get_class($this->appointment),
            'context_id' => $this->appointment->id,
            'allow_time_entry' => true,
            'allow_context_management' => true,
            'can_link_to_entity' => true,
        ]);
    }

    public function createAgendaSlot()
    {
        $this->authorize('update', $this->appointment);

        $maxOrder = $this->appointment->agendaSlots()->max('order') ?? 0;

        MeetingAgendaSlot::create([
            'appointment_id' => $this->appointment->id,
            'meeting_id' => $this->appointment->meeting_id,
            'name' => 'Neue Spalte',
            'order' => $maxOrder + 1,
        ]);

        $this->dispatch('agendaSlotUpdated');
    }

    public function updateAgendaSlotOrder($slotIds)
    {
        $this->authorize('update', $this->appointment);

        foreach ($slotIds as $order => $slotId) {
            MeetingAgendaSlot::where('id', $slotId)
                ->where('appointment_id', $this->appointment->id)
                ->update(['order' => $order]);
        }

        $this->dispatch('agendaSlotUpdated');
    }

    public function updateAgendaItemOrder($itemIds)
    {
        $this->authorize('update', $this->appointment);

        foreach ($itemIds as $order => $itemId) {
            MeetingAgendaItem::where('id', $itemId)
                ->where('appointment_id', $this->appointment->id)
                ->update(['order' => $order]);
        }

        $this->dispatch('agendaSlotUpdated');
    }

    public function createAgendaItem($slotId = null)
    {
        $this->authorize('update', $this->appointment);

        $maxOrder = $this->appointment->agendaItems()->max('order') ?? 0;

        MeetingAgendaItem::create([
            'appointment_id' => $this->appointment->id,
            'meeting_id' => $this->appointment->meeting_id,
            'agenda_slot_id' => $slotId,
            'title' => 'Neues Agenda Item',
            'order' => $maxOrder + 1,
            'status' => 'todo',
        ]);

        $this->dispatch('agendaSlotUpdated');
    }

    public function editAgendaItem($itemId)
    {
        $this->authorize('update', $this->appointment);
        
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
        $this->authorize('update', $this->appointment);
        
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
        $this->authorize('update', $this->appointment);
        
        MeetingAgendaItem::findOrFail($itemId)->delete();
        $this->dispatch('agendaSlotUpdated');
    }

    #[Computed]
    public function activities()
    {
        if (!$this->appointment) {
            return collect();
        }

        // TODO: Activity Log für Appointments implementieren
        return collect();
    }

    #[Computed]
    public function agendaStats()
    {
        $allItems = $this->appointment->agendaItems;
        
        return [
            [
                'title' => 'Offen',
                'count' => $allItems->where('status', 'todo')->count(),
                'icon' => 'clock',
                'variant' => 'warning'
            ],
            [
                'title' => 'In Progress',
                'count' => $allItems->where('status', 'in_progress')->count(),
                'icon' => 'arrow-path',
                'variant' => 'primary'
            ],
            [
                'title' => 'Erledigt',
                'count' => $allItems->where('status', 'done')->count(),
                'icon' => 'check-circle',
                'variant' => 'success'
            ],
            [
                'title' => 'Gesamt',
                'count' => $allItems->count(),
                'icon' => 'document-text',
                'variant' => 'secondary'
            ],
            [
                'title' => 'Ohne Verantwortung',
                'count' => $allItems->whereNull('assigned_to_id')->count(),
                'icon' => 'user',
                'variant' => 'muted'
            ],
        ];
    }

    public function render()
    {
        $user = Auth::user();

        // Agenda Slots mit Items laden
        $agendaSlots = $this->appointment->agendaSlots()
            ->with(['agendaItems' => function ($query) {
                $query->orderBy('order');
            }])
            ->get();

        // Backlog (Items ohne Slot)
        $backlogItems = $this->appointment->agendaItems()
            ->whereNull('agenda_slot_id')
            ->orderBy('order')
            ->get();

        // Done Slot
        $doneSlot = $agendaSlots->firstWhere('is_done_slot', true);
        $doneItems = $doneSlot ? $doneSlot->agendaItems : collect();

        // Aktive Slots (ohne Done)
        $activeSlots = $agendaSlots->reject(fn($slot) => $slot->is_done_slot);

        // Team-Mitglieder für Zuweisung
        $teamMembers = $this->appointment->meeting->team->users()
            ->orderBy('name')
            ->get();

        return view('meetings::livewire.appointment', [
            'agendaSlots' => $activeSlots,
            'backlogItems' => $backlogItems,
            'doneItems' => $doneItems,
            'doneSlot' => $doneSlot,
            'activities' => $this->activities,
            'teamMembers' => $teamMembers,
            'agendaStats' => $this->agendaStats,
        ])->layout('platform::layouts.app');
    }
}
