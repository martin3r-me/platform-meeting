<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'meetings_appointments';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'team_id',
        'start_date', // Konkreter Termin
        'end_date', // Konkreter Termin
        'location', // Optional: Überschreibt Meeting-Location
        'microsoft_event_id',
        'sync_status',
        'last_synced_at',
        'sync_error',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Team-ID automatisch setzen, falls nicht gesetzt
            if (!$model->team_id && Auth::check() && Auth::user()->currentTeam) {
                $model->team_id = Auth::user()->currentTeam->id;
            }
        });
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team()
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function agendaSlots()
    {
        return $this->hasMany(\Platform\Meetings\Models\MeetingAgendaSlot::class, 'appointment_id')->orderBy('order');
    }

    public function agendaItems()
    {
        return $this->hasMany(\Platform\Meetings\Models\MeetingAgendaItem::class, 'appointment_id')->orderBy('order');
    }
}

