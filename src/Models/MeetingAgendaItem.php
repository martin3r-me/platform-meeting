<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingAgendaItem extends Model
{
    use HasFactory;

    protected $table = 'meetings_agenda_items';

    protected $fillable = [
        'meeting_id',
        'appointment_id',
        'agenda_slot_id',
        'assigned_to_id',
        'title',
        'description',
        'order',
        'status',
        'duration_minutes',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function appointment()
    {
        return $this->belongsTo(\Platform\Meetings\Models\Appointment::class);
    }

    public function agendaSlot()
    {
        return $this->belongsTo(MeetingAgendaSlot::class, 'agenda_slot_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'assigned_to_id');
    }
}

