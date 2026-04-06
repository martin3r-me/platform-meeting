<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('meetings_agenda_slots');
        Schema::dropIfExists('meetings_appointments');
        Schema::dropIfExists('meetings_microsoft_calendar_subscriptions');
        Schema::dropIfExists('meetings_recurring_meetings');
    }

    public function down(): void
    {
        // Tables cannot be recreated - use original migrations to restore
    }
};
