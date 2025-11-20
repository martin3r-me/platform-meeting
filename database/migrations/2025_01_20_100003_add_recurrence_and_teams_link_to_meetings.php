<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recurrence Pattern (von Microsoft Graph API) - nur hinzufügen wenn nicht vorhanden
        if (!Schema::hasColumn('meetings_meetings', 'recurrence_type')) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->string('recurrence_type')->nullable()->after('microsoft_online_meeting_id'); // daily, weekly, monthly, yearly
            });
        }
        
        if (!Schema::hasColumn('meetings_meetings', 'recurrence_interval')) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->integer('recurrence_interval')->nullable()->after('recurrence_type'); // z.B. 1 = jede Woche, 2 = alle 2 Wochen
            });
        }
        
        if (!Schema::hasColumn('meetings_meetings', 'recurrence_days_of_week')) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->json('recurrence_days_of_week')->nullable()->after('recurrence_interval'); // ['monday', 'wednesday'] für weekly
            });
        }
        
        // recurrence_start_date und recurrence_end_date existieren bereits (aus früherer Migration)
        // Diese werden nicht hinzugefügt, da sie bereits vorhanden sind
        
        // Teams Meeting Link - nur hinzufügen wenn nicht vorhanden
        if (!Schema::hasColumn('meetings_meetings', 'microsoft_teams_join_url')) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->string('microsoft_teams_join_url')->nullable()->after('microsoft_online_meeting_id');
            });
        }
        
        if (!Schema::hasColumn('meetings_meetings', 'microsoft_teams_web_url')) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->text('microsoft_teams_web_url')->nullable()->after('microsoft_teams_join_url'); // Web-URL zum Meeting
            });
        }
        
        // Index für Series Master (für schnelle Suche nach Serien) - nur hinzufügen wenn nicht vorhanden
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $indexExists = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, 'meetings_meetings', 'meetings_meetings_microsoft_series_master_id_index']
        );
        
        if ($indexExists[0]->count == 0) {
            Schema::table('meetings_meetings', function (Blueprint $table) {
                $table->index('microsoft_series_master_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $table->dropIndex(['microsoft_series_master_id']);
            $table->dropColumn([
                'recurrence_type',
                'recurrence_interval',
                'recurrence_days_of_week',
                'recurrence_start_date',
                'recurrence_end_date',
                'microsoft_teams_join_url',
                'microsoft_teams_web_url',
            ]);
        });
    }
};

