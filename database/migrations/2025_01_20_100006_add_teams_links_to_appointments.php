<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_appointments', function (Blueprint $table) {
            // Teams Meeting Links - primär im Appointment (jede Instanz kann einen eigenen Link haben)
            // Fallback: Meeting hat den Standard-Link für alle Instanzen
            if (!Schema::hasColumn('meetings_appointments', 'microsoft_teams_join_url')) {
                $table->string('microsoft_teams_join_url')->nullable()->after('microsoft_event_id');
            }
            if (!Schema::hasColumn('meetings_appointments', 'microsoft_teams_web_url')) {
                $table->text('microsoft_teams_web_url')->nullable()->after('microsoft_teams_join_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings_appointments', function (Blueprint $table) {
            $table->dropColumn([
                'microsoft_teams_join_url',
                'microsoft_teams_web_url',
            ]);
        });
    }
};

