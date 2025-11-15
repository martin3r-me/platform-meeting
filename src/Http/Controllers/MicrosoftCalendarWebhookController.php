<?php

namespace Platform\Meetings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Platform\Meetings\Models\MicrosoftCalendarSubscription;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;

class MicrosoftCalendarWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Validation Token (bei Subscription-Erstellung)
        if ($request->has('validationToken')) {
            return response($request->input('validationToken'), 200)
                ->header('Content-Type', 'text/plain');
        }

        // Notification empfangen
        $notifications = $request->input('value', []);

        foreach ($notifications as $notification) {
            $subscriptionId = $notification['subscriptionId'] ?? null;
            $clientState = $notification['clientState'] ?? null;
            $resource = $notification['resource'] ?? null;

            // Subscription validieren
            $subscription = MicrosoftCalendarSubscription::where('subscription_id', $subscriptionId)
                ->where('client_state', $clientState)
                ->first();

            if (!$subscription) {
                Log::warning('Microsoft Calendar Webhook: Invalid subscription', [
                    'subscription_id' => $subscriptionId,
                ]);
                continue;
            }

            // Resource-Daten abrufen (Event-Details)
            if ($resource) {
                $this->processResourceChange($subscription, $resource);
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    protected function processResourceChange($subscription, $resource)
    {
        try {
            // Event-ID aus Resource extrahieren
            // Resource Format: /me/calendar/events/{eventId}
            if (preg_match('/\/events\/([^\/]+)/', $resource, $matches)) {
                $eventId = $matches[1];

                // Meeting finden
                $meeting = Meeting::where('microsoft_event_id', $eventId)->first();

                if ($meeting) {
                    // RSVP-Status synchronisieren
                    $calendarService = app(MicrosoftGraphCalendarService::class);
                    $calendarService->syncParticipantResponses($meeting);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Microsoft Calendar Webhook: Error processing resource change', [
                'error' => $e->getMessage(),
                'resource' => $resource,
            ]);
        }
    }
}

