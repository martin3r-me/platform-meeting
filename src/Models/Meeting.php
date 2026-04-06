<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Auth;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Contracts\HasKeyResultAncestors;

class Meeting extends Model implements HasDisplayName, HasKeyResultAncestors
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'meetings_meetings';

    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'meeting_series_id',
        'title',
        'description',
        'location',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
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

    public function series()
    {
        return $this->belongsTo(MeetingSeries::class, 'meeting_series_id');
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function agendaItems()
    {
        return $this->hasMany(MeetingAgendaItem::class)->orderBy('order');
    }

    public function notes()
    {
        return $this->hasMany(MeetingNote::class)->orderBy('created_at', 'desc');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    /**
     * Prüft ob es ein Serientermin ist
     */
    public function isRecurring(): bool
    {
        return !empty($this->meeting_series_id);
    }

    /**
     * Gibt den Location-Typ zurück
     */
    public function getLocationType(): string
    {
        if (empty($this->location)) {
            return 'other';
        }

        $location = strtolower($this->location);

        if (str_contains($location, 'teams') || str_contains($location, 'microsoft teams')) {
            return 'teams';
        }

        if (filter_var($this->location, FILTER_VALIDATE_URL) ||
            str_contains($location, 'zoom') ||
            str_contains($location, 'google meet') ||
            str_contains($location, 'webex')) {
            return 'online';
        }

        return 'room';
    }

    /**
     * Prüft ob es ein Online-Meeting ist
     */
    public function isOnlineMeeting(): bool
    {
        return in_array($this->getLocationType(), ['teams', 'online']);
    }

    /**
     * Prüft ob es ein Raum ist
     */
    public function isRoom(): bool
    {
        return $this->getLocationType() === 'room';
    }

    public function keyResultAncestors(): array
    {
        return [];
    }
}
