<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable()->after('status');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->foreignId('meeting_series_id')->nullable()->after('team_id')
                ->constrained('meetings_series')->nullOnDelete();
        });

        // Datenmigration: start_date/end_date vom ersten Appointment kopieren
        if (Schema::hasTable('meetings_appointments')) {
            DB::statement("
                UPDATE meetings_meetings m
                SET start_date = (
                    SELECT a.start_date FROM meetings_appointments a
                    WHERE a.meeting_id = m.id
                    ORDER BY a.start_date ASC
                    LIMIT 1
                ),
                end_date = (
                    SELECT a.end_date FROM meetings_appointments a
                    WHERE a.meeting_id = m.id
                    ORDER BY a.start_date ASC
                    LIMIT 1
                )
                WHERE m.start_date IS NULL
            ");
        }

        // Drop MS365 and recurrence columns
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $columns = [];
            $allColumns = Schema::getColumnListing('meetings_meetings');

            foreach ([
                'microsoft_event_id',
                'microsoft_series_master_id',
                'is_series_instance',
                'microsoft_online_meeting_id',
                'microsoft_teams_join_url',
                'microsoft_teams_web_url',
                'recurrence_type',
                'recurrence_interval',
                'recurrence_days_of_week',
                'recurrence_start_date',
                'recurrence_end_date',
                'recurring_meeting_id',
            ] as $col) {
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
        Schema::table('meetings_meetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meeting_series_id');
            $table->dropColumn(['start_date', 'end_date']);

            $table->string('microsoft_event_id')->nullable();
            $table->string('microsoft_series_master_id')->nullable();
            $table->boolean('is_series_instance')->default(false);
            $table->string('microsoft_online_meeting_id')->nullable();
            $table->string('microsoft_teams_join_url')->nullable();
            $table->string('microsoft_teams_web_url')->nullable();
            $table->string('recurrence_type')->nullable();
            $table->integer('recurrence_interval')->nullable();
            $table->json('recurrence_days_of_week')->nullable();
            $table->date('recurrence_start_date')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->foreignId('recurring_meeting_id')->nullable();
        });
    }
};
