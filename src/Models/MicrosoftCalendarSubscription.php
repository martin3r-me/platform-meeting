<?php

namespace Platform\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MicrosoftCalendarSubscription extends Model
{
    use HasFactory;

    protected $table = 'meetings_microsoft_calendar_subscriptions';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'resource',
        'change_type',
        'notification_url',
        'client_state',
        'expiration_date_time',
    ];

    protected $casts = [
        'expiration_date_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expiration_date_time);
    }
}

