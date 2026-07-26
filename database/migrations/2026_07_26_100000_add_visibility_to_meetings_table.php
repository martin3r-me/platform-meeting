<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings_meetings', 'visibility')) {
                // Standard: 'team' – neue Meetings sind für das ganze Team sichtbar.
                $table->string('visibility', 20)->default('team')->after('status');
                $table->index(['team_id', 'visibility']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings_meetings', 'visibility')) {
                $table->dropIndex(['team_id', 'visibility']);
                $table->dropColumn('visibility');
            }
        });
    }
};
