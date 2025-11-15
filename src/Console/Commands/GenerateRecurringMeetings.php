<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Meetings\Models\RecurringMeeting;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;
use Carbon\Carbon;

class GenerateRecurringMeetings extends Command
{
    protected $signature = 'meetings:generate-recurring {--months=12 : Anzahl Monate in die Zukunft}';
    protected $description = 'Generiert Meetings aus aktiven Serienterminen für die nächsten X Monate';

    public function handle()
    {
        $months = (int) $this->option('months');
        $untilDate = now()->addMonths($months);

        $recurringMeetings = RecurringMeeting::where('is_active', true)
            ->get();

        $totalCreated = 0;

        foreach ($recurringMeetings as $recurring) {
            // Prüfe ob RecurringMeeting überhaupt aktiv ist und ein Startdatum hat
            if (!$recurring->next_meeting_date) {
                // Wenn kein next_meeting_date gesetzt, setze es auf heute
                $recurring->next_meeting_date = now();
                $recurring->save();
            }

            // Erstelle alle fehlenden Meetings bis zum Enddatum
            $createdMeetings = $recurring->createMeetingsUntil($untilDate);
            
            if (count($createdMeetings) > 0) {
                $calendarService = app(MicrosoftGraphCalendarService::class);
                
                foreach ($createdMeetings as $meeting) {
                    // Zu Microsoft Calendar syncen
                    $calendarService->createEvent($meeting);
                    
                    $totalCreated++;
                    $this->info("Created meeting: {$meeting->title} ({$meeting->start_date->format('d.m.Y H:i')})");
                }
            }
        }

        $this->info("Created {$totalCreated} meetings from recurring meetings (until {$untilDate->format('d.m.Y')}).");
        return 0;
    }
}

