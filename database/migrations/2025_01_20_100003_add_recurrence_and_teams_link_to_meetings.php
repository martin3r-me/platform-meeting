<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            // Recurrence Pattern (von Microsoft Graph API)
            $table->string('recurrence_type')->nullable()->after('microsoft_online_meeting_id'); // daily, weekly, monthly, yearly
            $table->integer('recurrence_interval')->nullable()->after('recurrence_type'); // z.B. 1 = jede Woche, 2 = alle 2 Wochen
            $table->json('recurrence_days_of_week')->nullable()->after('recurrence_interval'); // ['monday', 'wednesday'] für weekly
            $table->date('recurrence_start_date')->nullable()->after('recurrence_days_of_week');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_start_date');
            
            // Teams Meeting Link
            $table->string('microsoft_teams_join_url')->nullable()->after('microsoft_online_meeting_id');
            $table->text('microsoft_teams_web_url')->nullable()->after('microsoft_teams_join_url'); // Web-URL zum Meeting
            
            // Index für Series Master (für schnelle Suche nach Serien)
            $table->index('microsoft_series_master_id');
        });
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

