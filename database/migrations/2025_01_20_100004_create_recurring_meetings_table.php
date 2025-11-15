<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            
            $table->string('recurrence_type'); // daily, weekly, monthly, yearly
            $table->integer('recurrence_interval')->default(1); // z.B. 1 = jede Woche, 2 = alle 2 Wochen
            $table->date('recurrence_end_date')->nullable();
            $table->datetime('next_meeting_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Microsoft Graph API
            $table->string('microsoft_series_master_id')->nullable()->unique();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'next_meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_meetings');
    }
};

