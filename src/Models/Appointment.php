<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'meetings_appointments';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'microsoft_event_id',
        'sync_status',
        'last_synced_at',
        'sync_error',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }
}

