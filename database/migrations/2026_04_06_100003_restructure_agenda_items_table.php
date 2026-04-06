<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: only add type column if it doesn't exist yet
        if (!Schema::hasColumn('meetings_agenda_items', 'type')) {
            Schema::table('meetings_agenda_items', function (Blueprint $table) {
                $table->string('type')->default('topic')->after('meeting_id');
            });
        }

        // Datenmigration: meeting_id von appointment holen wo nötig
        if (Schema::hasColumn('meetings_agenda_items', 'appointment_id') && Schema::hasTable('meetings_appointments')) {
            DB::statement("
                UPDATE meetings_agenda_items ai
                SET meeting_id = (
                    SELECT a.meeting_id FROM meetings_appointments a
                    WHERE a.id = ai.appointment_id
                    LIMIT 1
                )
                WHERE ai.meeting_id IS NULL AND ai.appointment_id IS NOT NULL
            ");
        }

        // Drop foreign keys first (MySQL requires this before dropping columns)
        Schema::table('meetings_agenda_items', function (Blueprint $table) {
            $allColumns = Schema::getColumnListing('meetings_agenda_items');
            if (in_array('agenda_slot_id', $allColumns)) {
                $table->dropForeign(['agenda_slot_id']);
            }
            if (in_array('appointment_id', $allColumns)) {
                $table->dropForeign(['appointment_id']);
            }
        });

        // Drop obsolete columns
        Schema::table('meetings_agenda_items', function (Blueprint $table) {
            $columns = [];
            $allColumns = Schema::getColumnListing('meetings_agenda_items');

            foreach (['appointment_id', 'agenda_slot_id'] as $col) {
                if (in_array($col, $allColumns)) {
                    $columns[] = $col;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings_agenda_items', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignId('appointment_id')->nullable();
            $table->foreignId('agenda_slot_id')->nullable();
        });
    }
};
