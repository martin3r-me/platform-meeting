<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microsoft_calendar_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('subscription_id')->unique(); // Microsoft Graph Subscription ID
            $table->string('resource'); // z.B. /me/calendar/events
            $table->string('change_type'); // created, updated, deleted
            $table->string('notification_url');
            $table->string('client_state'); // Secret für Validierung
            $table->datetime('expiration_date_time');
            
            $table->timestamps();
            
            $table->index(['user_id', 'expiration_date_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microsoft_calendar_subscriptions');
    }
};

