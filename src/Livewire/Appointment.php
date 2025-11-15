<?php

namespace Platform\Meetings\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Meetings\Models\Appointment as AppointmentModel;

class Appointment extends Component
{
    public AppointmentModel $appointment;

    public function mount(AppointmentModel $appointment)
    {
        $this->appointment = $appointment;
        $this->authorize('view', $this->appointment);
    }

    public function render()
    {
        return view('meetings::livewire.appointment')->layout('platform::layouts.app');
    }
}

