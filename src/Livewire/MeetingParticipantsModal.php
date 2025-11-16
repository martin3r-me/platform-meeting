<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class MeetingParticipantsModal extends Component
{
    public $modalShow = false;
    public $meeting;
    public $teamUsers = [];

    #[On('open-modal-meeting-participants')]
    public function openModalMeetingParticipants($meetingId)
    {
        $this->meeting = Meeting::with(['participants.user', 'team.users'])->findOrFail($meetingId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->meeting);
        
        // Team-Mitglieder holen (für Auswahl)
        $this->teamUsers = $this->meeting->team->users()
            ->orderBy('name')
            ->get();
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
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
        MeetingParticipant::create([
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
        
        MeetingParticipant::where('meeting_id', $this->meeting->id)
            ->where('user_id', $userId)
            ->delete();
        
        $this->meeting->refresh();
    }

    public function getAvailableUsers()
    {
        if (!$this->meeting) {
            return collect();
        }
        
        // Alle Team-Mitglieder, die noch nicht Teilnehmer sind
        $participantUserIds = $this->meeting->participants()->pluck('user_id')->toArray();
        
        return $this->teamUsers->filter(function ($user) use ($participantUserIds) {
            return !in_array($user->id, $participantUserIds);
        });
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->dispatch('updateMeeting');
    }

    public function render()
    {
        return view('meetings::livewire.meeting-participants-modal');
    }
}

