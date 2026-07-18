<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase C: MS365-Serien-Identität auf dem Meeting-Workspace.
 *
 * series_master_id ist der Schlüssel für "eine Instanz pro Serie": bei der
 * Promotion eines Inbox-Termins wird per find-or-create genau ein Meeting je
 * Serie erzeugt, an das alle Vorkommen andocken. Unique erlaubt beliebig viele
 * NULLs (Einzeltermine ohne Serie).
 *
 * Nicht zu verwechseln mit meeting_series_id → das ist die App-interne
 * Recurrence (MeetingSeries), die unabhängig bestehen bleibt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $table->string('series_master_id')->nullable()->after('meeting_series_id');
            $table->unique('series_master_id', 'meetings_series_master_unique');
        });
    }

    public function down(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $table->dropUnique('meetings_series_master_unique');
            $table->dropColumn('series_master_id');
        });
    }
};
