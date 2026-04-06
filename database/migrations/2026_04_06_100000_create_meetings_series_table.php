<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings_series', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('recurrence_type')->nullable(); // weekly, biweekly, monthly, quarterly, yearly
            $table->unsignedTinyInteger('recurrence_day_of_week')->nullable(); // 0=Sunday, 1=Monday, ...
            $table->unsignedTinyInteger('recurrence_day_of_month')->nullable(); // 1-31
            $table->boolean('is_active')->default(true);
            $table->dateTime('next_meeting_date')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings_series');
    }
};
