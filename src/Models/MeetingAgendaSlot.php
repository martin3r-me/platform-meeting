<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingAgendaSlot extends Model
{
    use HasFactory;

    protected $table = 'meetings_agenda_slots';

    protected $fillable = [
        'meeting_id',
        'name',
        'order',
        'is_done_slot',
    ];

    protected $casts = [
        'is_done_slot' => 'boolean',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItems()
    {
        return $this->hasMany(MeetingAgendaItem::class)->orderBy('order');
    }
}

