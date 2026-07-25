<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * iCalUId als geteilte Identität der Meeting-Instanz: ein realer Termin → genau ein
 * Meeting, an das alle Beteiligten (über ihre eigenen Inbox-Items) andocken.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meetings_meetings')) {
            return;
        }

        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (! Schema::hasColumn('meetings_meetings', 'ical_uid')) {
                $table->string('ical_uid')->nullable()->after('series_master_id');
                $table->index('ical_uid', 'meetings_ical_uid_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('meetings_meetings')) {
            return;
        }

        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings_meetings', 'ical_uid')) {
                $table->dropIndex('meetings_ical_uid_idx');
                $table->dropColumn('ical_uid');
            }
        });
    }
};
