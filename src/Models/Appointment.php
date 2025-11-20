<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;

class Appointment extends Model implements HasTimeAncestors, HasKeyResultAncestors
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
        'microsoft_teams_join_url', // Teams Join-Link (falls Instanz-spezifisch)
        'microsoft_teams_web_url', // Teams Web-URL (falls Instanz-spezifisch)
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

    /**
     * Gibt den Teams Join-Link zurück (falls vorhanden)
     * Zuerst im Appointment (falls Instanz-spezifisch), dann Fallback auf Meeting
     */
    public function getTeamsJoinUrl(): ?string
    {
        return $this->microsoft_teams_join_url 
            ?? $this->microsoft_teams_web_url
            ?? $this->meeting?->getTeamsJoinUrl()
            ?? null;
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die Zeitkaskade zurück.
     * Appointment → Meeting (als Root)
     */
    public function timeAncestors(): array
    {
        $ancestors = [];

        // Meeting als Root-Kontext (bei Appointments ist das Meeting immer der Root)
        if ($this->meeting) {
            $ancestors[] = [
                'type' => get_class($this->meeting),
                'id' => $this->meeting->id,
                'is_root' => true, // Meeting ist Root-Kontext für Appointments
                'label' => $this->meeting->title,
            ];
        }

        return $ancestors;
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die KeyResult-Kaskade zurück.
     * Appointment → Meeting (als Root)
     */
    public function keyResultAncestors(): array
    {
        $ancestors = [];

        // Meeting als Root-Kontext (bei Appointments ist das Meeting immer der Root)
        if ($this->meeting) {
            $ancestors[] = [
                'type' => get_class($this->meeting),
                'id' => $this->meeting->id,
                'is_root' => true, // Meeting ist Root-Kontext für Appointments
                'label' => $this->meeting->title,
            ];
        }

        return $ancestors;
    }
}

