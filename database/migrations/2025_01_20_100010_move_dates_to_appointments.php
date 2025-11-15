<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. start_date und end_date zu appointments hinzufügen
        Schema::table('meetings_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings_appointments', 'start_date')) {
                $table->datetime('start_date')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('meetings_appointments', 'end_date')) {
                $table->datetime('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('meetings_appointments', 'location')) {
                $table->string('location')->nullable()->after('end_date');
            }
        });

        // 2. Daten migrieren: Für jedes Meeting ein Appointment mit den Daten erstellen
        // (nur wenn noch keine Appointments existieren)
        $meetingsWithDates = DB::table('meetings_meetings')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        foreach ($meetingsWithDates as $meeting) {
            // Prüfe ob bereits Appointments für dieses Meeting existieren
            $existingAppointments = DB::table('meetings_appointments')
                ->where('meeting_id', $meeting->id)
                ->count();

            if ($existingAppointments === 0) {
                // Erstelle Appointment für den Organizer (user_id des Meetings)
                DB::table('meetings_appointments')->insert([
                    'meeting_id' => $meeting->id,
                    'user_id' => $meeting->user_id,
                    'team_id' => $meeting->team_id,
                    'start_date' => $meeting->start_date,
                    'end_date' => $meeting->end_date,
                    'location' => $meeting->location,
                    'sync_status' => 'pending',
                    'created_at' => $meeting->created_at ?? now(),
                    'updated_at' => $meeting->updated_at ?? now(),
                ]);
            } else {
                // Wenn Appointments existieren, aber keine start_date haben, aktualisiere sie
                DB::table('meetings_appointments')
                    ->where('meeting_id', $meeting->id)
                    ->whereNull('start_date')
                    ->update([
                        'start_date' => $meeting->start_date,
                        'end_date' => $meeting->end_date,
                        'location' => $meeting->location,
                    ]);
            }
        }

        // 3. recurrence_start_date und recurrence_end_date zu meetings hinzufügen (für Serien)
        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings_meetings', 'recurrence_start_date')) {
                $table->datetime('recurrence_start_date')->nullable()->after('location');
            }
            if (!Schema::hasColumn('meetings_meetings', 'recurrence_end_date')) {
                $table->datetime('recurrence_end_date')->nullable()->after('recurrence_start_date');
            }
        });

        // 4. start_date, end_date von meetings entfernen (nach Migration)
        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings_meetings', 'start_date')) {
                $table->dropIndex(['start_date', 'end_date']); // Index entfernen
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('meetings_meetings', 'end_date')) {
                $table->dropColumn('end_date');
            }
            // location bleibt in Meeting als Standard-Location
            // (kann in Appointment überschrieben werden)
        });

        // 4. Index auf appointments.start_date hinzufügen
        Schema::table('meetings_appointments', function (Blueprint $table) {
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        // 1. start_date, end_date zu meetings zurück hinzufügen
        Schema::table('meetings_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings_meetings', 'start_date')) {
                $table->datetime('start_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('meetings_meetings', 'end_date')) {
                $table->datetime('end_date')->nullable()->after('start_date');
            }
        });

        // 2. Daten zurück migrieren (vom ersten Appointment)
        $appointments = DB::table('meetings_appointments')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('created_at')
            ->get()
            ->groupBy('meeting_id');

        foreach ($appointments as $meetingId => $appointmentGroup) {
            $firstAppointment = $appointmentGroup->first();
            
            DB::table('meetings_meetings')
                ->where('id', $meetingId)
                ->update([
                    'start_date' => $firstAppointment->start_date,
                    'end_date' => $firstAppointment->end_date,
                ]);
        }

        // 3. start_date, end_date, location von appointments entfernen
        Schema::table('meetings_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('meetings_appointments', 'start_date')) {
                $table->dropIndex(['start_date']);
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('meetings_appointments', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('meetings_appointments', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};

