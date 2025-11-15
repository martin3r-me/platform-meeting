<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ändere agenda_slots: meeting_id -> appointment_id
        if (Schema::hasColumn('meetings_agenda_slots', 'meeting_id')) {
            Schema::table('meetings_agenda_slots', function (Blueprint $table) {
                $table->foreignId('appointment_id')->nullable()->after('meeting_id')->constrained('meetings_appointments')->cascadeOnDelete();
            });
            
            // Migriere bestehende Daten: Erstelle Appointments für Meetings
            // (wird später gefüllt, wenn Appointments existieren)
            
            // Entferne meeting_id später (erst nach Migration der Daten)
            // $table->dropForeign(['meeting_id']);
            // $table->dropColumn('meeting_id');
        }
        
        // Ändere agenda_items: meeting_id -> appointment_id
        if (Schema::hasColumn('meetings_agenda_items', 'meeting_id')) {
            Schema::table('meetings_agenda_items', function (Blueprint $table) {
                $table->foreignId('appointment_id')->nullable()->after('meeting_id')->constrained('meetings_appointments')->cascadeOnDelete();
            });
            
            // Entferne meeting_id später (erst nach Migration der Daten)
            // $table->dropForeign(['meeting_id']);
            // $table->dropColumn('meeting_id');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meetings_agenda_slots', 'appointment_id')) {
            Schema::table('meetings_agenda_slots', function (Blueprint $table) {
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            });
        }
        
        if (Schema::hasColumn('meetings_agenda_items', 'appointment_id')) {
            Schema::table('meetings_agenda_items', function (Blueprint $table) {
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            });
        }
    }
};

