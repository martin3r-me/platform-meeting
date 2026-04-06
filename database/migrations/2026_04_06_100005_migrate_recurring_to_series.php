<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meetings_recurring_meetings')) {
            return;
        }

        // Daten von meetings_recurring_meetings → meetings_series kopieren
        $recurringMeetings = DB::table('meetings_recurring_meetings')->get();

        foreach ($recurringMeetings as $rm) {
            $seriesId = DB::table('meetings_series')->insertGetId([
                'uuid' => $rm->uuid,
                'user_id' => $rm->user_id,
                'team_id' => $rm->team_id,
                'title' => $rm->title,
                'description' => $rm->description,
                'location' => $rm->location,
                'start_time' => $rm->start_time,
                'end_time' => $rm->end_time,
                'recurrence_type' => $rm->recurrence_type,
                'is_active' => $rm->is_active,
                'next_meeting_date' => $rm->next_meeting_date,
                'recurrence_end_date' => $rm->recurrence_end_date,
                'created_at' => $rm->created_at,
                'updated_at' => $rm->updated_at,
                'deleted_at' => $rm->deleted_at ?? null,
            ]);

            // meetings_meetings.meeting_series_id updaten
            DB::table('meetings_meetings')
                ->where('recurring_meeting_id', $rm->id)
                ->update(['meeting_series_id' => $seriesId]);
        }
    }

    public function down(): void
    {
        // Nicht reversibel - Daten bleiben in meetings_series
    }
};
