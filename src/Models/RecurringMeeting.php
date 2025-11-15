<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\Auth;
use Platform\ActivityLog\Traits\LogsActivity;

class RecurringMeeting extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_date',
        'next_meeting_date',
        'is_active',
        'microsoft_series_master_id',
    ];

    protected $casts = [
        'recurrence_end_date' => 'date',
        'next_meeting_date' => 'datetime',
        'is_active' => 'boolean',
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

            if (! $model->recurrence_interval) {
                $model->recurrence_interval = 1;
            }

            if ($model->is_active === null) {
                $model->is_active = true;
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

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'recurring_meeting_id');
    }

    /**
     * Erstellt ein Meeting basierend auf diesem Serientermin
     */
    public function createMeeting(): Meeting
    {
        $startDate = $this->next_meeting_date->copy();
        $startDate->setTimeFromTimeString($this->start_time);
        
        $endDate = $this->next_meeting_date->copy();
        $endDate->setTimeFromTimeString($this->end_time);

        $meeting = Meeting::create([
            'user_id' => $this->user_id,
            'team_id' => $this->team_id,
            'recurring_meeting_id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->location,
            'status' => 'planned',
            'is_series_instance' => true,
            'microsoft_series_master_id' => $this->microsoft_series_master_id,
        ]);

        $this->calculateNextMeetingDate();
        $this->save();

        return $meeting;
    }

    /**
     * Berechnet das nächste Meeting-Datum basierend auf dem Wiederholungsmuster
     */
    public function calculateNextMeetingDate(): void
    {
        if (!$this->next_meeting_date) {
            $this->next_meeting_date = now();
        }

        $current = $this->next_meeting_date;

        $this->next_meeting_date = match($this->recurrence_type) {
            'daily' => $current->copy()->addDays($this->recurrence_interval),
            'weekly' => $current->copy()->addWeeks($this->recurrence_interval),
            'monthly' => $current->copy()->addMonths($this->recurrence_interval),
            'yearly' => $current->copy()->addYears($this->recurrence_interval),
            default => $current->copy()->addDay(),
        };
    }

    /**
     * Prüft, ob ein neues Meeting erstellt werden sollte
     */
    public function shouldCreateMeeting(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->recurrence_end_date && now()->isAfter($this->recurrence_end_date)) {
            return false;
        }

        if (!$this->next_meeting_date) {
            return false;
        }

        return now()->isSameDay($this->next_meeting_date) || now()->isAfter($this->next_meeting_date);
    }
}

