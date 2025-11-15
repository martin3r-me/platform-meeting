<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
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
}

