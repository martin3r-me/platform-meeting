<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Meetings\Models\MeetingSeries;
use Carbon\Carbon;

class GenerateRecurringMeetings extends Command
{
    protected $signature = 'meetings:generate-recurring {--months=12 : Anzahl Monate in die Zukunft}';
    protected $description = 'Generiert Meetings aus aktiven Serien für die nächsten X Monate';

    public function handle()
    {
        $months = (int) $this->option('months');
        $untilDate = now()->addMonths($months);

        $seriesList = MeetingSeries::where('is_active', true)->get();

        $totalCreated = 0;

        foreach ($seriesList as $series) {
            if (!$series->next_meeting_date) {
                $series->next_meeting_date = now();
                $series->save();
            }

            $createdMeetings = $series->createMeetingsUntil($untilDate);

            foreach ($createdMeetings as $meeting) {
                $totalCreated++;
                $this->info("Created meeting: {$meeting->title} ({$meeting->start_date->format('d.m.Y H:i')})");
            }
        }

        $this->info("Created {$totalCreated} meetings from series (until {$untilDate->format('d.m.Y')}).");
        return 0;
    }
}
