<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Auth;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Media\Traits\HasMedia;
use Platform\Core\Contracts\HasDisplayName;

class Meeting extends Model implements HasDisplayName
{
    use HasFactory, SoftDeletes, LogsActivity, HasMedia;

    protected $table = 'meetings_meetings';

    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'recurring_meeting_id',
        'title',
        'description',
        'location', // Standard-Location (kann in Appointment überschrieben werden)
        'status',
        'microsoft_event_id',
        'microsoft_series_master_id',
        'is_series_instance',
        'microsoft_online_meeting_id',
        // Recurring-Pattern (wenn Meeting eine Serie ist)
        'recurrence_start_date', // Wann startet die Serie
        'recurrence_end_date', // Wann endet die Serie (optional)
    ];

    protected $casts = [
        'is_series_instance' => 'boolean',
        'recurrence_start_date' => 'datetime',
        'recurrence_end_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (! $model->user_id) {
                $model->user_id = Auth::id();
            }

            if (! $model->team_id) {
                $model->team_id = Auth::user()->currentTeam->id ?? null;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team()
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function recurringMeeting()
    {
        return $this->belongsTo(RecurringMeeting::class, 'recurring_meeting_id');
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    // Agenda gehört jetzt zu Appointments, nicht mehr direkt zu Meetings

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    /**
     * Prüft ob es ein Teams Call ist
     */
    public function isTeamsCall(): bool
    {
        return !empty($this->microsoft_online_meeting_id) || 
               (str_contains(strtolower($this->location ?? ''), 'teams') ||
                str_contains(strtolower($this->location ?? ''), 'microsoft teams'));
    }

    /**
     * Prüft ob es ein Raum ist
     */
    public function isRoom(): bool
    {
        if (empty($this->location)) {
            return false;
        }
        
        // Wenn es ein Teams Call ist, ist es kein Raum
        if ($this->isTeamsCall()) {
            return false;
        }
        
        // Wenn es eine URL ist (z.B. Zoom, Google Meet), ist es kein Raum
        if (filter_var($this->location, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Ansonsten ist es wahrscheinlich ein Raum
        return true;
    }

    /**
     * Prüft ob es ein Online-Meeting ist (Teams, Zoom, etc.)
     */
    public function isOnlineMeeting(): bool
    {
        if ($this->isTeamsCall()) {
            return true;
        }
        
        if (empty($this->location)) {
            return false;
        }
        
        $location = strtolower($this->location);
        return str_contains($location, 'zoom') ||
               str_contains($location, 'google meet') ||
               str_contains($location, 'webex') ||
               filter_var($this->location, FILTER_VALIDATE_URL);
    }

    /**
     * Gibt den Location-Typ zurück
     */
    public function getLocationType(): string
    {
        if ($this->isTeamsCall()) {
            return 'teams';
        }
        
        if ($this->isOnlineMeeting()) {
            return 'online';
        }
        
        if ($this->isRoom()) {
            return 'room';
        }
        
        return 'other';
    }

    /**
     * Prüft ob es ein Serientermin ist
     */
    public function isRecurring(): bool
    {
        return !empty($this->recurring_meeting_id) || $this->is_series_instance;
    }
}

