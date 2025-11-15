<?php

namespace Platform\Meetings\Services;

use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\RecurringMeeting;
use Platform\Meetings\Models\MeetingParticipant;
use Platform\Meetings\Models\Appointment;
use Platform\Meetings\Models\MicrosoftCalendarSubscription;
use Platform\Core\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MicrosoftGraphCalendarService
{
    protected string $baseUrl = 'https://graph.microsoft.com/v1.0';

    /**
     * Holt Access Token für einen User (aus SSO oder gespeichertem Token)
     */
    protected function getAccessToken(User $user): ?string
    {
        // TODO: Token aus User-Modell oder OAuth-Token-Tabelle holen
        // Für jetzt: Token aus Session/Request holen (wenn verfügbar)
        return request()->header('X-Microsoft-Access-Token') 
            ?? session('microsoft_access_token_' . $user->id);
    }

    /**
     * Erstellt ein Event in Microsoft Calendar
     */
    public function createEvent(Meeting $meeting): ?array
    {
        $organizer = $meeting->user;
        $token = $this->getAccessToken($organizer);

        if (!$token) {
            Log::warning('Microsoft Graph: No access token for user', ['user_id' => $organizer->id]);
            return null;
        }

        $attendees = $meeting->participants()
            ->with('user')
            ->get()
            ->map(function ($participant) {
                return [
                    'emailAddress' => [
                        'address' => $participant->user->email,
                        'name' => $participant->user->name,
                    ],
                    'type' => $participant->role === 'organizer' ? 'required' : 'optional',
                ];
            })
            ->toArray();

        $body = [
            'subject' => $meeting->title,
            'body' => [
                'contentType' => 'HTML',
                'content' => $meeting->description ?? '',
            ],
            'start' => [
                'dateTime' => $meeting->start_date->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'end' => [
                'dateTime' => $meeting->end_date->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'location' => $meeting->location ? [
                'displayName' => $meeting->location,
            ] : null,
            'attendees' => $attendees,
            'isOrganizer' => true,
            'isReminderOn' => true,
            'reminderMinutesBeforeStart' => 15,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/me/calendar/events", $body);

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Failed to create event', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $eventData = $response->json();
            
            // Meeting aktualisieren mit Microsoft Event ID
            $meeting->update([
                'microsoft_event_id' => $eventData['id'],
            ]);

            // Appointments für alle Teilnehmer erstellen
            $this->syncToParticipants($meeting, $eventData);

            return $eventData;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception creating event', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Aktualisiert ein Event in Microsoft Calendar
     */
    public function updateEvent(Meeting $meeting): ?array
    {
        if (!$meeting->microsoft_event_id) {
            return $this->createEvent($meeting);
        }

        $organizer = $meeting->user;
        $token = $this->getAccessToken($organizer);

        if (!$token) {
            return null;
        }

        $attendees = $meeting->participants()
            ->with('user')
            ->get()
            ->map(function ($participant) {
                return [
                    'emailAddress' => [
                        'address' => $participant->user->email,
                        'name' => $participant->user->name,
                    ],
                    'type' => $participant->role === 'organizer' ? 'required' : 'optional',
                ];
            })
            ->toArray();

        $body = [
            'subject' => $meeting->title,
            'body' => [
                'contentType' => 'HTML',
                'content' => $meeting->description ?? '',
            ],
            'start' => [
                'dateTime' => $meeting->start_date->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'end' => [
                'dateTime' => $meeting->end_date->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'location' => $meeting->location ? [
                'displayName' => $meeting->location,
            ] : null,
            'attendees' => $attendees,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->patch("{$this->baseUrl}/me/calendar/events/{$meeting->microsoft_event_id}", $body);

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Failed to update event', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception updating event', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Löscht ein Event in Microsoft Calendar
     */
    public function deleteEvent(Meeting $meeting): bool
    {
        if (!$meeting->microsoft_event_id) {
            return true;
        }

        $organizer = $meeting->user;
        $token = $this->getAccessToken($organizer);

        if (!$token) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->delete("{$this->baseUrl}/me/calendar/events/{$meeting->microsoft_event_id}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception deleting event', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Erstellt Appointments für alle Teilnehmer
     */
    public function syncToParticipants(Meeting $meeting, ?array $eventData = null): void
    {
        if (!$eventData && $meeting->microsoft_event_id) {
            // Event-Daten von Graph API holen
            $organizer = $meeting->user;
            $token = $this->getAccessToken($organizer);
            
            if ($token) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get("{$this->baseUrl}/me/calendar/events/{$meeting->microsoft_event_id}?\$expand=attendees");
                
                if ($response->successful()) {
                    $eventData = $response->json();
                }
            }
        }

        foreach ($meeting->participants as $participant) {
            Appointment::updateOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $participant->user_id,
                ],
                [
                    'microsoft_event_id' => $eventData['id'] ?? $meeting->microsoft_event_id,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]
            );
        }
    }

    /**
     * Synchronisiert RSVP-Status von Microsoft Graph API
     */
    public function syncParticipantResponses(Meeting $meeting): void
    {
        if (!$meeting->microsoft_event_id) {
            return;
        }

        $organizer = $meeting->user;
        $token = $this->getAccessToken($organizer);

        if (!$token) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("{$this->baseUrl}/me/calendar/events/{$meeting->microsoft_event_id}?\$expand=attendees");

            if (!$response->successful()) {
                return;
            }

            $eventData = $response->json();
            $attendees = $eventData['attendees'] ?? [];

            foreach ($attendees as $attendee) {
                $email = $attendee['emailAddress']['address'] ?? null;
                if (!$email) {
                    continue;
                }

                $participant = $meeting->participants()
                    ->whereHas('user', fn($q) => $q->where('email', $email))
                    ->first();

                if ($participant) {
                    $responseStatus = $this->mapResponseStatus($attendee['status']['response'] ?? 'notResponded');
                    
                    $participant->update([
                        'response_status' => $responseStatus,
                        'response_time' => $attendee['status']['time'] ?? now(),
                        'microsoft_attendee_id' => $attendee['id'] ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception syncing participant responses', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Erstellt einen Serientermin in Microsoft Calendar
     */
    public function createRecurringEvent(RecurringMeeting $recurring): ?array
    {
        $organizer = $recurring->user;
        $token = $this->getAccessToken($organizer);

        if (!$token) {
            return null;
        }

        $recurrencePattern = $this->buildRecurrencePattern($recurring);

        $body = [
            'subject' => $recurring->title,
            'body' => [
                'contentType' => 'HTML',
                'content' => $recurring->description ?? '',
            ],
            'start' => [
                'dateTime' => $recurring->next_meeting_date->copy()->setTimeFromTimeString($recurring->start_time)->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'end' => [
                'dateTime' => $recurring->next_meeting_date->copy()->setTimeFromTimeString($recurring->end_time)->toIso8601String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'location' => $recurring->location ? [
                'displayName' => $recurring->location,
            ] : null,
            'recurrence' => $recurrencePattern,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/me/calendar/events", $body);

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Failed to create recurring event', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $eventData = $response->json();
            
            $recurring->update([
                'microsoft_series_master_id' => $eventData['seriesMasterId'] ?? $eventData['id'],
            ]);

            return $eventData;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception creating recurring event', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Baut Recurrence Pattern für Microsoft Graph API
     */
    protected function buildRecurrencePattern(RecurringMeeting $recurring): array
    {
        $pattern = [
            'pattern' => [
                'type' => ucfirst($recurring->recurrence_type),
                'interval' => $recurring->recurrence_interval,
            ],
            'range' => [
                'type' => $recurring->recurrence_end_date ? 'endDate' : 'noEnd',
                'startDate' => $recurring->next_meeting_date->format('Y-m-d'),
            ],
        ];

        if ($recurring->recurrence_end_date) {
            $pattern['range']['endDate'] = $recurring->recurrence_end_date->format('Y-m-d');
        }

        // Wochentage für weekly
        if ($recurring->recurrence_type === 'weekly') {
            $pattern['pattern']['daysOfWeek'] = [$recurring->next_meeting_date->format('l')];
        }

        return $pattern;
    }

    /**
     * Erstellt Subscription für Webhooks
     */
    public function createSubscription(User $user, string $resource = '/me/calendar/events'): ?array
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            return null;
        }

        $clientState = bin2hex(random_bytes(16));
        $notificationUrl = route('meetings.webhook.microsoft-calendar');
        $expiration = now()->addDays(2)->toIso8601String(); // Max 3 Tage

        $body = [
            'changeType' => 'created,updated,deleted',
            'notificationUrl' => $notificationUrl,
            'resource' => $resource,
            'expirationDateTime' => $expiration,
            'clientState' => $clientState,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/subscriptions", $body);

            if (!$response->successful()) {
                return null;
            }

            $subscriptionData = $response->json();

            MicrosoftCalendarSubscription::create([
                'user_id' => $user->id,
                'subscription_id' => $subscriptionData['id'],
                'resource' => $resource,
                'change_type' => 'created,updated,deleted',
                'notification_url' => $notificationUrl,
                'client_state' => $clientState,
                'expiration_date_time' => $expiration,
            ]);

            return $subscriptionData;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception creating subscription', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Erneuert eine Subscription
     */
    public function renewSubscription(string $subscriptionId): bool
    {
        $subscription = MicrosoftCalendarSubscription::where('subscription_id', $subscriptionId)->first();
        if (!$subscription) {
            return false;
        }

        $token = $this->getAccessToken($subscription->user);
        if (!$token) {
            return false;
        }

        $expiration = now()->addDays(2)->toIso8601String();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->patch("{$this->baseUrl}/subscriptions/{$subscriptionId}", [
                'expirationDateTime' => $expiration,
            ]);

            if ($response->successful()) {
                $subscription->update([
                    'expiration_date_time' => $expiration,
                ]);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception renewing subscription', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mappt Microsoft Response Status zu unserem Format
     */
    protected function mapResponseStatus(string $microsoftStatus): string
    {
        return match($microsoftStatus) {
            'none' => 'notResponded',
            'organizer' => 'organizer',
            'tentative' => 'tentative',
            'accepted' => 'accepted',
            'declined' => 'declined',
            default => 'notResponded',
        };
    }

    /**
     * Holt verfügbare Räume/Ressourcen aus dem Tenant
     */
    public function findRooms(User $user, ?string $startDateTime = null, ?string $endDateTime = null): array
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            return [];
        }

        try {
            // Zuerst die Room Lists finden
            $roomListsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("{$this->baseUrl}/me/findRooms");

            if (!$roomListsResponse->successful()) {
                Log::warning('Microsoft Graph: Failed to find room lists', [
                    'status' => $roomListsResponse->status(),
                    'body' => $roomListsResponse->body(),
                ]);
                return [];
            }

            $roomLists = $roomListsResponse->json('value', []);
            $rooms = [];

            // Für jede Room List die Räume holen
            foreach ($roomLists as $roomList) {
                $emailAddress = $roomList['emailAddress']['address'] ?? null;
                if (!$emailAddress) {
                    continue;
                }

                // Räume aus dieser Liste holen
                $roomsResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get("{$this->baseUrl}/me/findRooms(RoomList='{$emailAddress}')");

                if ($roomsResponse->successful()) {
                    $roomListRooms = $roomsResponse->json('value', []);
                    foreach ($roomListRooms as $room) {
                        $rooms[] = [
                            'id' => $room['emailAddress']['address'] ?? null,
                            'name' => $room['name'] ?? $room['emailAddress']['name'] ?? $room['emailAddress']['address'],
                            'email' => $room['emailAddress']['address'] ?? null,
                            'address' => $room['address'] ?? null,
                            'capacity' => $room['capacity'] ?? null,
                        ];
                    }
                }
            }

            // Optional: Verfügbarkeit prüfen, wenn Start/Ende angegeben
            if ($startDateTime && $endDateTime) {
                $availableRooms = $this->checkRoomAvailability($user, $rooms, $startDateTime, $endDateTime);
                return $availableRooms;
            }

            return $rooms;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception finding rooms', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Prüft Verfügbarkeit von Räumen für einen Zeitraum
     */
    protected function checkRoomAvailability(User $user, array $rooms, string $startDateTime, string $endDateTime): array
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            return $rooms;
        }

        $roomEmails = array_column($rooms, 'email');
        if (empty($roomEmails)) {
            return $rooms;
        }

        try {
            $body = [
                'schedules' => $roomEmails,
                'startTime' => [
                    'dateTime' => $startDateTime,
                    'timeZone' => config('app.timezone', 'Europe/Berlin'),
                ],
                'endTime' => [
                    'dateTime' => $endDateTime,
                    'timeZone' => config('app.timezone', 'Europe/Berlin'),
                ],
                'availabilityViewInterval' => 60,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/me/calendar/getSchedule", $body);

            if (!$response->successful()) {
                return $rooms;
            }

            $scheduleData = $response->json('value', []);
            $roomAvailability = [];

            foreach ($scheduleData as $schedule) {
                $email = $schedule['scheduleId'] ?? null;
                $availability = $schedule['availabilityView'] ?? '';
                
                // Prüfe ob der Raum verfügbar ist (keine 'busy' Slots)
                $isAvailable = !str_contains($availability, '1'); // '1' = busy
                $roomAvailability[$email] = $isAvailable;
            }

            // Markiere Räume als verfügbar/nicht verfügbar
            foreach ($rooms as &$room) {
                $room['available'] = $roomAvailability[$room['email']] ?? true;
            }

            return $rooms;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception checking room availability', [
                'error' => $e->getMessage(),
            ]);
            return $rooms;
        }
    }

    /**
     * Holt zukünftige Events aus Microsoft Calendar für einen User
     */
    public function getFutureEvents(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            Log::warning('Microsoft Graph: No access token for user', ['user_id' => $user->id]);
            return [];
        }

        $startDate = $startDate ?? now();
        $endDate = $endDate ?? now()->addMonths(3); // Standard: 3 Monate in die Zukunft

        try {
            $url = "{$this->baseUrl}/me/calendar/calendarView";
            $params = [
                'startDateTime' => $startDate->toIso8601String(),
                'endDateTime' => $endDate->toIso8601String(),
                '$orderby' => 'start/dateTime',
                '$top' => 100, // Max 100 Events pro Request
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get($url, $params);

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Failed to get events', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $events = $response->json('value', []);

            // Wenn es mehr Events gibt, weitere Seiten holen
            $nextLink = $response->json('@odata.nextLink');
            while ($nextLink) {
                $nextResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get($nextLink);

                if ($nextResponse->successful()) {
                    $nextEvents = $nextResponse->json('value', []);
                    $events = array_merge($events, $nextEvents);
                    $nextLink = $nextResponse->json('@odata.nextLink');
                } else {
                    break;
                }
            }

            return $events;
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception getting events', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Holt alle Instanzen eines Recurring Events
     */
    public function getRecurringEventInstances(User $user, string $seriesMasterId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $token = $this->getAccessToken($user);
        if (!$token) {
            return [];
        }

        $startDate = $startDate ?? now();
        $endDate = $endDate ?? now()->addMonths(3);

        try {
            $url = "{$this->baseUrl}/me/calendar/events/{$seriesMasterId}/instances";
            $params = [
                'startDateTime' => $startDate->toIso8601String(),
                'endDateTime' => $endDate->toIso8601String(),
                '$orderby' => 'start/dateTime',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get($url, $params);

            if (!$response->successful()) {
                Log::error('Microsoft Graph: Failed to get recurring event instances', [
                    'user_id' => $user->id,
                    'series_master_id' => $seriesMasterId,
                    'status' => $response->status(),
                ]);
                return [];
            }

            return $response->json('value', []);
        } catch (\Throwable $e) {
            Log::error('Microsoft Graph: Exception getting recurring event instances', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

