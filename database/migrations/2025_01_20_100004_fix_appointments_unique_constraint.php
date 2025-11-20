<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        // SCHRITT 1: Duplikate bereinigen (behält das neueste Appointment pro microsoft_event_id)
        $duplicates = $connection->select(
            "SELECT microsoft_event_id, COUNT(*) as count 
             FROM meetings_appointments 
             WHERE microsoft_event_id IS NOT NULL 
             GROUP BY microsoft_event_id 
             HAVING count > 1"
        );
        
        foreach ($duplicates as $duplicate) {
            // Behalte das neueste Appointment, lösche die älteren
            $keepId = $connection->selectOne(
                "SELECT id FROM meetings_appointments 
                 WHERE microsoft_event_id = ? 
                 ORDER BY created_at DESC, id DESC 
                 LIMIT 1",
                [$duplicate->microsoft_event_id]
            );
            
            if ($keepId) {
                // Lösche alle anderen Duplikate
                $connection->statement(
                    "DELETE FROM meetings_appointments 
                     WHERE microsoft_event_id = ? AND id != ?",
                    [$duplicate->microsoft_event_id, $keepId->id]
                );
            }
        }
        
        // SCHRITT 2: Alte Unique Constraint entfernen (wenn vorhanden)
        $indexExists = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, 'meetings_appointments', 'meetings_appointments_meeting_id_user_id_unique']
        );
        
        if ($indexExists[0]->count > 0) {
            // Foreign Key Constraints finden und temporär entfernen
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
                 AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                 AND CONSTRAINT_NAME LIKE 'meetings_appointments_%_foreign'",
                [$databaseName, 'meetings_appointments']
            );
            
            // Foreign Keys temporär entfernen
            foreach ($foreignKeys as $fk) {
                Schema::table('meetings_appointments', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }
            
            // Index löschen
            Schema::table('meetings_appointments', function (Blueprint $table) {
                $table->dropUnique(['meeting_id', 'user_id']);
            });
            
            // Foreign Keys wieder hinzufügen
            Schema::table('meetings_appointments', function (Blueprint $table) {
                $table->foreign('meeting_id')->references('id')->on('meetings_meetings')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
        
        // SCHRITT 3: Neue Unique Constraints hinzufügen (nur wenn noch nicht vorhanden)
        $microsoftEventIdIndex = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, 'meetings_appointments', 'meetings_appointments_microsoft_event_id_unique']
        );
        
        $meetingUserStartIndex = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, 'meetings_appointments', 'meetings_appointments_meeting_user_start_unique']
        );
        
        Schema::table('meetings_appointments', function (Blueprint $table) use ($microsoftEventIdIndex, $meetingUserStartIndex) {
            if ($microsoftEventIdIndex[0]->count == 0) {
                $table->unique('microsoft_event_id', 'meetings_appointments_microsoft_event_id_unique');
            }
            
            if ($meetingUserStartIndex[0]->count == 0) {
                $table->unique(['meeting_id', 'user_id', 'start_date'], 'meetings_appointments_meeting_user_start_unique');
            }
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

