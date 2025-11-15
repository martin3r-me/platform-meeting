<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\MeetingAgendaItem as AgendaItemModel;
use Livewire\Attributes\Computed;

class AgendaItem extends Component
{
    public AgendaItemModel $agendaItem;

    protected $rules = [
        'agendaItem.title' => 'required|string|max:255',
        'agendaItem.description' => 'nullable|string',
        'agendaItem.duration_minutes' => 'nullable|integer|min:1',
        'agendaItem.assigned_to_id' => 'nullable|exists:users,id',
        'agendaItem.status' => 'required|in:todo,in_progress,done',
    ];

    public function mount(AgendaItemModel $agendaItem)
    {
        $this->authorize('view', $agendaItem->appointment);
        $this->agendaItem = $agendaItem->load(['appointment', 'appointment.meeting', 'appointment.user', 'assignedTo', 'agendaSlot']);
    }

    #[Computed]
    public function isDirty(): bool
    {
        if (!$this->agendaItem) {
            return false;
        }
        return count($this->agendaItem->getDirty()) > 0;
    }

    #[Computed]
    public function activities()
    {
        if (!$this->agendaItem) {
            return collect();
        }

        // TODO: Activity Log für Agenda Items implementieren
        return collect();
    }

    #[Computed]
    public function teamUsers()
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            return collect();
        }

        return $user->currentTeam->users()
            ->orderBy('name')
            ->get();
    }

    public function save()
    {
        $this->authorize('update', $this->agendaItem->appointment);
        
        $this->validate();
        $this->agendaItem->save();

        $this->dispatch('agendaItemUpdated');
    }

    public function delete()
    {
        $this->authorize('update', $this->agendaItem->appointment);
        
        $appointment = $this->agendaItem->appointment;
        $this->agendaItem->delete();

        return redirect()->route('meetings.appointments.show', $appointment);
    }

    public function render()
    {
        $user = Auth::user();

        return view('meetings::livewire.agenda-item', [
            'activities' => $this->activities,
            'teamUsers' => $this->teamUsers,
        ])->layout('platform::layouts.app');
    }
}

