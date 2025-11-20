<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_appointments', function (Blueprint $table) {
            // Entferne alte Unique Constraint (meeting_id + user_id)
            // Diese verhindert mehrere Appointments für Recurring Events
            $table->dropUnique(['meeting_id', 'user_id']);
        });
        
        // Neue Unique Constraints über DB::statement, da Blueprint nullable unique nicht gut unterstützt
        Schema::table('meetings_appointments', function (Blueprint $table) {
            // Unique Constraint für microsoft_event_id (wenn nicht NULL)
            // Für Recurring Events: Jede Instanz hat eine eindeutige microsoft_event_id
            // Für einzelne Events: microsoft_event_id ist ebenfalls eindeutig
            // NULL-Werte sind erlaubt (mehrere NULL-Werte sind in MySQL erlaubt)
            $table->unique('microsoft_event_id', 'meetings_appointments_microsoft_event_id_unique');
            
            // Zusätzlich: Unique Constraint für meeting_id + user_id + start_date
            // (für den Fall, dass kein microsoft_event_id vorhanden ist oder für manuell erstellte Appointments)
            $table->unique(['meeting_id', 'user_id', 'start_date'], 'meetings_appointments_meeting_user_start_unique');
        });
    }

    public function down(): void
    {
        Schema::table('meetings_appointments', function (Blueprint $table) {
            $table->dropUnique('meetings_appointments_microsoft_event_id_unique');
            $table->dropUnique('meetings_appointments_meeting_user_start_unique');
            $table->unique(['meeting_id', 'user_id']);
        });
    }
};

