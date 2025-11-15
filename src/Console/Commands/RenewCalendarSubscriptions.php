<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Meetings\Models\MicrosoftCalendarSubscription;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;
use Carbon\Carbon;

class RenewCalendarSubscriptions extends Command
{
    protected $signature = 'meetings:renew-subscriptions';
    protected $description = 'Erneuert ablaufende Microsoft Calendar Subscriptions';

    public function handle()
    {
        // Subscriptions die in den nächsten 24 Stunden ablaufen
        $expiringSoon = MicrosoftCalendarSubscription::where('expiration_date_time', '<=', now()->addDay())
            ->get();

        $renewed = 0;
        $failed = 0;

        foreach ($expiringSoon as $subscription) {
            $calendarService = app(MicrosoftGraphCalendarService::class);
            
            if ($calendarService->renewSubscription($subscription->subscription_id)) {
                $renewed++;
                $this->info("Renewed subscription: {$subscription->subscription_id}");
            } else {
                $failed++;
                $this->warn("Failed to renew subscription: {$subscription->subscription_id}");
            }
        }

        $this->info("Renewed {$renewed} subscriptions. {$failed} failed.");
        return 0;
    }
}

