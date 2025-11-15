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
        'start_date',
        'end_date',
        'location',
        'status',
        'microsoft_event_id',
        'microsoft_series_master_id',
        'is_series_instance',
        'microsoft_online_meeting_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_series_instance' => 'boolean',
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

    public function agendaSlots()
    {
        return $this->hasMany(MeetingAgendaSlot::class)->orderBy('order');
    }

    public function agendaItems()
    {
        return $this->hasMany(MeetingAgendaItem::class)->orderBy('order');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}

