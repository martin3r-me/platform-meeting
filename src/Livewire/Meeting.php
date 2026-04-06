<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Meeting as MeetingModel;
use Platform\Meetings\Models\MeetingAgendaItem;
use Platform\Meetings\Models\MeetingNote;
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
        'type' => 'topic',
    ];

    // Note editing
    public $newNoteContent = '';

    #[On('updateMeeting')]
    public function updateMeeting()
    {
        $this->meeting->refresh();
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
            'linked_contexts' => [],
            'allow_time_entry' => true,
            'allow_entities' => false,
            'allow_dimensions' => false,
        ]);

        $this->dispatch('keyresult', [
            'context_type' => get_class($this->meeting),
            'context_id' => $this->meeting->id,
        ]);
    }

    public function createAgendaItem($type = 'topic')
    {
        $this->authorize('update', $this->meeting);

        $maxOrder = $this->meeting->agendaItems()->max('order') ?? 0;

        MeetingAgendaItem::create([
            'meeting_id' => $this->meeting->id,
            'type' => $type,
            'title' => 'Neues Agenda Item',
            'order' => $maxOrder + 1,
            'status' => 'todo',
        ]);

        $this->meeting->refresh();
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
            'type' => $item->type ?? 'topic',
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
            'editingAgendaItem.type' => 'required|in:topic,decision,action_item,info',
        ]);

        $item = MeetingAgendaItem::findOrFail($this->editingAgendaItemId);
        $item->update([
            'title' => $this->editingAgendaItem['title'],
            'description' => $this->editingAgendaItem['description'],
            'duration_minutes' => $this->editingAgendaItem['duration_minutes'],
            'assigned_to_id' => $this->editingAgendaItem['assigned_to_id'],
            'type' => $this->editingAgendaItem['type'],
        ]);

        $this->cancelEditAgendaItem();
        $this->meeting->refresh();
    }

    public function cancelEditAgendaItem()
    {
        $this->editingAgendaItemId = null;
        $this->editingAgendaItem = [
            'title' => '',
            'description' => '',
            'duration_minutes' => null,
            'assigned_to_id' => null,
            'type' => 'topic',
        ];
    }

    public function deleteAgendaItem($itemId)
    {
        $this->authorize('update', $this->meeting);

        MeetingAgendaItem::findOrFail($itemId)->delete();
        $this->meeting->refresh();
    }

    public function updateAgendaItemOrder($itemIds)
    {
        $this->authorize('update', $this->meeting);

        foreach ($itemIds as $order => $itemId) {
            MeetingAgendaItem::where('id', $itemId)
                ->where('meeting_id', $this->meeting->id)
                ->update(['order' => $order]);
        }

        $this->meeting->refresh();
    }

    public function saveNote()
    {
        $this->authorize('view', $this->meeting);

        $this->validate([
            'newNoteContent' => 'required|string|min:1',
        ]);

        MeetingNote::create([
            'meeting_id' => $this->meeting->id,
            'user_id' => Auth::id(),
            'content' => $this->newNoteContent,
            'is_published' => false,
        ]);

        $this->newNoteContent = '';
        $this->meeting->refresh();
    }

    public function toggleNotePublished($noteId)
    {
        $this->authorize('update', $this->meeting);

        $note = MeetingNote::findOrFail($noteId);
        $note->is_published = !$note->is_published;
        $note->save();

        $this->meeting->refresh();
    }

    public function deleteNote($noteId)
    {
        $note = MeetingNote::findOrFail($noteId);

        // Nur der Autor oder der Meeting-Organizer kann löschen
        if ($note->user_id !== Auth::id() && $this->meeting->user_id !== Auth::id()) {
            abort(403);
        }

        $note->delete();
        $this->meeting->refresh();
    }

    public function deleteMeeting()
    {
        $this->authorize('delete', $this->meeting);

        $this->meeting->delete();

        return redirect()->route('meetings.dashboard');
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

        $translations = [
            'created' => 'erstellt',
            'updated' => 'aktualisiert',
            'deleted' => 'gelöscht',
            'manual' => 'hat eine Nachricht hinzugefügt',
        ];

        $translatedName = $translations[$activityName] ?? $activityName;

        if ($activity->message) {
            return "{$userName}: {$activity->message}";
        }

        if ($activity->properties && !empty($activity->properties)) {
            $props = $activity->properties;
            $changedFields = [];

            if (isset($props['old']) || isset($props['new'])) {
                if (isset($props['old']) && isset($props['new'])) {
                    $changedFields = array_keys($props['new']);
                } elseif (isset($props['new'])) {
                    $changedFields = array_keys($props['new']);
                }
            } else {
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

    public function render()
    {
        $user = Auth::user();

        $agendaItems = $this->meeting->agendaItems()->with('assignedTo')->get();
        $notes = $this->meeting->notes()->with('user')->get();

        $teamMembers = $this->meeting->team->users()
            ->orderBy('name')
            ->get();

        return view('meetings::livewire.meeting', [
            'activities' => $this->activities,
            'agendaItems' => $agendaItems,
            'notes' => $notes,
            'teamMembers' => $teamMembers,
        ])->layout('platform::layouts.app');
    }
}
