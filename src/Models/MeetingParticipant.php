<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $table = 'meetings_participants';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'email', // Für externe Teilnehmer ohne User-Account
        'name', // Für externe Teilnehmer ohne User-Account
        'role',
        'response_status',
        'response_time',
        'microsoft_attendee_id',
    ];

    protected $casts = [
        'response_time' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    /**
     * Gibt den Anzeigenamen zurück (User oder externer Teilnehmer)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->fullname ?? $this->user->name;
        }
        
        return $this->name ?? $this->email ?? 'Unbekannt';
    }

    /**
     * Prüft ob es ein externer Teilnehmer ist (ohne User-Account)
     */
    public function isExternal(): bool
    {
        return empty($this->user_id) && !empty($this->email);
    }
}

