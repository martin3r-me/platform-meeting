<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Core\Models\User;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;
use Platform\Meetings\Models\Appointment;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncCalendarEvents extends Command
{
    protected $signature = 'meetings:sync-calendar-events 
                            {--user= : Sync nur für einen bestimmten User (ID)}
                            {--days=90 : Anzahl Tage in die Zukunft (Standard: 90)}';
    
    protected $description = 'Synchronisiert zukünftige Termine aus Microsoft Calendar als Meetings/Appointments';

    public function handle()
    {
        $days = (int) $this->option('days');
        $userId = $this->option('user');
        
        $startDate = now();
        $endDate = now()->addDays($days);

        $this->info("Synchronisiere Calendar Events von {$startDate->format('d.m.Y')} bis {$endDate->format('d.m.Y')}...");

        // User mit Azure-ID finden (haben Microsoft Account)
        $query = User::whereNotNull('azure_id');
        if ($userId) {
            $query->where('id', $userId);
        }
        
        $users = $query->get();
        
        if ($users->isEmpty()) {
            $this->warn('Keine User mit Azure-ID gefunden.');
            return 0;
        }

        $this->info("Gefunden: {$users->count()} User(s)");

        $calendarService = app(MicrosoftGraphCalendarService::class);
        $totalMeetings = 0;
        $totalAppointments = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $this->line("Verarbeite User: {$user->name} ({$user->email})");
            
            try {
                // Prüfe zuerst, ob Token verfügbar ist
                $tokenModel = \Platform\Core\Models\MicrosoftOAuthToken::where('user_id', $user->id)->first();
                
                if (!$tokenModel) {
                    $this->warn("  → Kein Token in Datenbank gefunden. User muss sich einmal über Azure SSO einloggen.");
                    continue;
                }
                
                // Prüfe Token-Status
                $scopes = $tokenModel->scopes ?? [];
                $hasCalendarScope = in_array('Calendars.ReadWrite', $scopes) || 
                                   in_array('Calendars.Read', $scopes) || 
                                   in_array('Calendars.ReadWrite.Shared', $scopes);
                
                if (!$hasCalendarScope) {
                    $this->warn("  → Token hat keine Calendar-Scopes!");
                    $this->line("     Aktuelle Scopes: " . implode(', ', $scopes ?: ['Keine']));
                    $this->line("     User muss sich einmal über Azure SSO neu einloggen, um Calendar-Scopes zu erhalten.");
                    continue;
                }
                
                if ($tokenModel->isExpired()) {
                    if ($tokenModel->refresh_token) {
                        $this->line("  → Token abgelaufen, versuche automatisches Refresh...");
                        $this->line("     Abgelaufen am: " . $tokenModel->expires_at?->format('d.m.Y H:i:s'));
                        // Token-Refresh wird automatisch in getAccessToken() durchgeführt
                    } else {
                        $this->warn("  → Token abgelaufen und kein Refresh Token verfügbar. User muss sich einmal über Azure SSO einloggen.");
                        $this->line("     Abgelaufen am: " . $tokenModel->expires_at?->format('d.m.Y H:i:s'));
                        continue;
                    }
                } else {
                    $this->line("  → Token gültig bis: " . $tokenModel->expires_at?->format('d.m.Y H:i:s'));
                }
                
                // Events für diesen User holen
                try {
                    $this->line("  → Suche Events von " . $startDate->format('d.m.Y H:i') . " bis " . $endDate->format('d.m.Y H:i'));
                    $events = $calendarService->getFutureEvents($user, $startDate, $endDate);
                    
                    if (empty($events)) {
                        $this->warn("  → Token verfügbar, aber keine Events im Kalender gefunden");
                        $this->line("     Prüfe bitte die Logs für Details (Microsoft Graph API Response)");
                        continue;
                    }

                    $this->info("  → " . count($events) . " Event(s) gefunden");
                } catch (\RuntimeException $e) {
                    // Token-Problem wurde bereits oben geprüft, aber falls doch noch ein Problem auftritt
                    $this->error("  → " . $e->getMessage());
                    continue;
                } catch (\Throwable $e) {
                    $this->error("  → Fehler beim Abrufen der Events: " . $e->getMessage());
                    Log::error('Failed to get events for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }

                foreach ($events as $event) {
                    try {
                        $result = $this->processEvent($user, $event, $calendarService, $startDate, $endDate);
                        
                        if ($result['created']) {
                            $totalMeetings += $result['meetings'];
                            $totalAppointments += $result['appointments'];
                            if ($result['meetings'] > 1) {
                                $this->line("    ✓ {$result['meetings']} Meetings erstellt: {$result['title']}");
                            } else {
                                $this->line("    ✓ Meeting erstellt: {$result['title']}");
                            }
                        } else {
                            $skipped++;
                        }
                    } catch (\Throwable $e) {
                        Log::error('Failed to process calendar event', [
                            'user_id' => $user->id,
                            'event_id' => $event['id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        $this->error("    ✗ Fehler beim Verarbeiten: {$e->getMessage()}");
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to sync calendar events for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Fehler: {$e->getMessage()}");
            }
        }

        $this->info("\nZusammenfassung:");
        $this->info("  Meetings erstellt: {$totalMeetings}");
        $this->info("  Appointments erstellt: {$totalAppointments}");
        $this->info("  Übersprungen: {$skipped}");

        return 0;
    }

    protected function processEvent(User $user, array $event, MicrosoftGraphCalendarService $calendarService, Carbon $startDate, Carbon $endDate): array
    {
        $eventId = $event['id'] ?? null;
        if (!$eventId) {
            return ['created' => false, 'meetings' => 0, 'appointments' => 0, 'title' => ''];
        }

        // Prüfe ob Event bereits existiert
        $existingMeeting = Meeting::where('microsoft_event_id', $eventId)->first();
        if ($existingMeeting) {
            return ['created' => false, 'meetings' => 0, 'appointments' => 0, 'title' => $event['subject'] ?? ''];
        }

        // Prüfe ob es ein Recurring Event ist
        $seriesMasterId = $event['seriesMasterId'] ?? null;
        $isRecurring = !empty($seriesMasterId) || !empty($event['recurrence']);

        if ($isRecurring && $seriesMasterId) {
            // Recurring Event: Hole alle Instanzen
            $instances = $calendarService->getRecurringEventInstances($user, $seriesMasterId, $startDate, $endDate);
            
            $meetingsCreated = 0;
            $appointmentsCreated = 0;
            
            foreach ($instances as $instance) {
                $result = $this->createMeetingFromEvent($user, $instance, $seriesMasterId, true);
                if ($result['meeting']) {
                    $meetingsCreated++;
                    $appointmentsCreated += $result['appointments'];
                }
            }
            
            return [
                'created' => $meetingsCreated > 0,
                'meetings' => $meetingsCreated,
                'appointments' => $appointmentsCreated,
                'title' => $event['subject'] ?? '',
            ];
        } else {
            // Einzelnes Event
            $result = $this->createMeetingFromEvent($user, $event, null, false);
            
            return [
                'created' => $result['meeting'] !== null,
                'meetings' => $result['meeting'] ? 1 : 0,
                'appointments' => $result['appointments'],
                'title' => $event['subject'] ?? '',
            ];
        }
    }

    protected function createMeetingFromEvent(User $user, array $event, ?string $seriesMasterId = null, bool $isSeriesInstance = false): array
    {
        $eventId = $event['id'] ?? null;
        if (!$eventId) {
            return ['meeting' => null, 'appointments' => 0];
        }

        // Prüfe ob bereits existiert
        $existingMeeting = Meeting::where('microsoft_event_id', $eventId)->first();
        if ($existingMeeting) {
            return ['meeting' => $existingMeeting, 'appointments' => 0];
        }

        // Parse Datum/Zeit
        $startDateTime = Carbon::parse($event['start']['dateTime']);
        $endDateTime = Carbon::parse($event['end']['dateTime']);
        
        // Nur zukünftige Events
        if ($startDateTime->isPast()) {
            return ['meeting' => null, 'appointments' => 0];
        }

        // Team bestimmen (vom User)
        $teamId = $user->currentTeam->id ?? null;

        DB::beginTransaction();
        try {
            // Meeting erstellen
            $meeting = Meeting::create([
                'user_id' => $user->id,
                'team_id' => $teamId,
                'title' => $event['subject'] ?? 'Ohne Titel',
                'description' => $event['body']['content'] ?? $event['bodyPreview'] ?? null,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'location' => $event['location']['displayName'] ?? $event['location']['locationUri'] ?? null,
                'status' => 'planned',
                'microsoft_event_id' => $eventId,
                'microsoft_series_master_id' => $seriesMasterId,
                'is_series_instance' => $isSeriesInstance,
            ]);

            // Organizer als Participant hinzufügen
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => 'organizer',
                'response_status' => 'accepted',
            ]);

            // Teilnehmer aus Event holen und als Participants/Appointments hinzufügen
            $attendees = $event['attendees'] ?? [];
            $appointmentsCreated = 0;

            foreach ($attendees as $attendee) {
                $attendeeEmail = $attendee['emailAddress']['address'] ?? null;
                if (!$attendeeEmail) {
                    continue;
                }

                // User anhand Email finden
                $attendeeUser = User::where('email', $attendeeEmail)->first();
                if (!$attendeeUser) {
                    // User existiert nicht - nur als Participant hinzufügen (ohne Appointment)
                    continue;
                }

                // Participant hinzufügen
                $participant = MeetingParticipant::firstOrCreate(
                    [
                        'meeting_id' => $meeting->id,
                        'user_id' => $attendeeUser->id,
                    ],
                    [
                        'role' => $attendee['type'] === 'required' ? 'attendee' : 'optional',
                        'response_status' => $this->mapResponseStatus($attendee['status']['response'] ?? 'none'),
                    ]
                );

                // Appointment erstellen
                $appointment = Appointment::firstOrCreate(
                    [
                        'meeting_id' => $meeting->id,
                        'user_id' => $attendeeUser->id,
                    ],
                    [
                        'team_id' => $teamId,
                        'microsoft_event_id' => $eventId,
                        'sync_status' => 'synced',
                        'last_synced_at' => now(),
                    ]
                );

                if ($appointment->wasRecentlyCreated) {
                    $appointmentsCreated++;
                }
            }

            // Auch für den Organizer ein Appointment erstellen
            $organizerAppointment = Appointment::firstOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $user->id,
                ],
                [
                    'team_id' => $teamId,
                    'microsoft_event_id' => $eventId,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]
            );

            if ($organizerAppointment->wasRecentlyCreated) {
                $appointmentsCreated++;
            }

            DB::commit();

            return [
                'meeting' => $meeting,
                'appointments' => $appointmentsCreated,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create meeting from event', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function mapResponseStatus(string $microsoftStatus): string
    {
        return match($microsoftStatus) {
            'accepted' => 'accepted',
            'declined' => 'declined',
            'tentativelyAccepted' => 'tentative',
            'none', 'notResponded' => 'notResponded',
            default => 'notResponded',
        };
    }
}

