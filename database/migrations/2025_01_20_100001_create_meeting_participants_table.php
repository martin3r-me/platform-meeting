<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings_participants')) {
            return;
        }

        Schema::create('meetings_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('role')->default('attendee'); // organizer, attendee, optional
            $table->string('response_status')->default('notResponded'); // none, organizer, tentative, accepted, declined, notResponded
            $table->timestamp('response_time')->nullable();
            
            // Microsoft Graph API
            $table->string('microsoft_attendee_id')->nullable();
            
            $table->timestamps();
            
            $table->unique(['meeting_id', 'user_id']);
            $table->index('meeting_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings_participants');
    }
};

