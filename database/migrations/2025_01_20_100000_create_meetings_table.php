<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings_meetings')) {
            return;
        }

        Schema::create('meetings_meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('recurring_meeting_id')->nullable()->constrained('meetings_recurring_meetings')->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('location')->nullable();
            $table->string('status')->default('planned'); // planned, confirmed, cancelled, completed
            
            // Microsoft Graph API Integration
            $table->string('microsoft_event_id')->nullable()->unique();
            $table->string('microsoft_series_master_id')->nullable();
            $table->boolean('is_series_instance')->default(false);
            $table->string('microsoft_online_meeting_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['start_date', 'end_date']);
            $table->index('team_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings_meetings');
    }
};

