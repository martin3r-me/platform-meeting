<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\MeetingSeries;
use Livewire\Attributes\On;

class MeetingSeriesView extends Component
{
    public MeetingSeries $meetingSeries;

    public function mount(MeetingSeries $meetingSeries)
    {
        $this->meetingSeries = $meetingSeries;
        $this->authorize('view', $this->meetingSeries);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->meetingSeries),
            'modelId' => $this->meetingSeries->id,
            'subject' => $this->meetingSeries->title,
            'description' => $this->meetingSeries->description ?? '',
            'url' => route('meetings.series.show', $this->meetingSeries),
            'source' => 'meetings.series.view',
            'recipients' => [],
            'meta' => [
                'recurrence_type' => $this->meetingSeries->recurrence_type,
                'next_meeting_date' => $this->meetingSeries->next_meeting_date,
            ],
        ]);

        $this->dispatch('organization', [
            'context_type' => get_class($this->meetingSeries),
            'context_id' => $this->meetingSeries->id,
            'linked_contexts' => [],
            'allow_time_entry' => true,
            'allow_entities' => false,
            'allow_dimensions' => false,
        ]);

        $this->dispatch('keyresult', [
            'context_type' => get_class($this->meetingSeries),
            'context_id' => $this->meetingSeries->id,
        ]);
    }

    public function toggleActive()
    {
        $this->authorize('update', $this->meetingSeries);

        $this->meetingSeries->is_active = !$this->meetingSeries->is_active;
        $this->meetingSeries->save();
    }

    public function generateMeetings()
    {
        $this->authorize('update', $this->meetingSeries);

        $created = $this->meetingSeries->createMeetingsUntil(now()->addMonths(3));

        session()->flash('message', count($created) . ' Meeting(s) generiert.');
    }

    public function deleteSeries()
    {
        $this->authorize('delete', $this->meetingSeries);

        $this->meetingSeries->delete();

        return redirect()->route('meetings.dashboard');
    }

    public function render()
    {
        $meetings = $this->meetingSeries->meetings()
            ->orderBy('start_date', 'desc')
            ->get();

        return view('meetings::livewire.meeting-series', [
            'meetings' => $meetings,
        ])->layout('platform::layouts.app');
    }
}
