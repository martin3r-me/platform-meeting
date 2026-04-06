<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_agenda_items', function (Blueprint $table) {
            $table->string('type')->default('topic')->after('meeting_id'); // topic, decision, action_item, info
        });

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
