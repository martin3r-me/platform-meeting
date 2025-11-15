<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Meetings\Models\RecurringMeeting;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;

class GenerateRecurringMeetings extends Command
{
    protected $signature = 'meetings:generate-recurring';
    protected $description = 'Generiert Meetings aus aktiven Serienterminen';

    public function handle()
    {
        $recurringMeetings = RecurringMeeting::where('is_active', true)
            ->get();

        $created = 0;

        foreach ($recurringMeetings as $recurring) {
            if ($recurring->shouldCreateMeeting()) {
                $meeting = $recurring->createMeeting();
                
                // Zu Microsoft Calendar syncen
                $calendarService = app(MicrosoftGraphCalendarService::class);
                $calendarService->createEvent($meeting);
                
                $created++;
                $this->info("Created meeting: {$meeting->title} ({$meeting->start_date})");
            }
        }

        $this->info("Created {$created} meetings from recurring meetings.");
        return 0;
    }
}

