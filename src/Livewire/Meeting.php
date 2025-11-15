<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting as MeetingModel;
use Platform\Meetings\Models\MeetingAgendaSlot;
use Platform\Meetings\Models\MeetingAgendaItem;
use Livewire\Attributes\On;

class Meeting extends Component
{
    public MeetingModel $meeting;

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

    public function render()
    {
        $user = Auth::user();

        // Agenda Slots mit Items laden
        $agendaSlots = $this->meeting->agendaSlots()
            ->with(['agendaItems' => function ($query) {
                $query->orderBy('order');
            }])
            ->get();

        // Backlog (Items ohne Slot)
        $backlogItems = $this->meeting->agendaItems()
            ->whereNull('agenda_slot_id')
            ->orderBy('order')
            ->get();

        // Done Slot
        $doneSlot = $agendaSlots->firstWhere('is_done_slot', true);
        $doneItems = $doneSlot ? $doneSlot->agendaItems : collect();

        // Aktive Slots (ohne Done)
        $activeSlots = $agendaSlots->reject(fn($slot) => $slot->is_done_slot);

        return view('meetings::livewire.meeting', [
            'agendaSlots' => $activeSlots,
            'backlogItems' => $backlogItems,
            'doneItems' => $doneItems,
            'doneSlot' => $doneSlot,
        ]);
    }
}

