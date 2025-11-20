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
                // TODO: Calendar-Scope-Prüfung am Montag wieder aktivieren
                // $hasCalendarScope = in_array('Calendars.ReadWrite', $scopes) || 
                //                    in_array('Calendars.Read', $scopes) || 
                //                    in_array('Calendars.ReadWrite.Shared', $scopes);
                // 
                // if (!$hasCalendarScope) {
                //     $this->warn("  → Token hat keine Calendar-Scopes!");
                //     $this->line("     Aktuelle Scopes: " . implode(', ', $scopes ?: ['Keine']));
                //     $this->line("     User muss sich einmal über Azure SSO neu einloggen, um Calendar-Scopes zu erhalten.");
                //     continue;
                // }
                
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
            // Recurring Event: 
            // 1. Erstelle/Finde EIN Meeting für die Serie (Elternelement)
            // 2. Hole alle Instanzen und erstelle Appointments (konkrete Termine)
            
            // Prüfe ob Meeting für diese Serie bereits existiert
            $meeting = Meeting::where('microsoft_series_master_id', $seriesMasterId)->first();
            
            if (!$meeting) {
                // Erstelle Meeting für die Serie (mit Recurrence Pattern)
                $meeting = $this->createSeriesMeeting($user, $event, $seriesMasterId);
                
                // Teilnehmer aus Series Master Event verarbeiten (einmalig beim Erstellen)
                if ($meeting) {
                    $this->syncParticipantsFromEvent($meeting, $event);
                }
            }
            
            if (!$meeting) {
                return ['created' => false, 'meetings' => 0, 'appointments' => 0, 'title' => $event['subject'] ?? ''];
            }
            
            // Hole alle Instanzen und erstelle Appointments
            $instances = $calendarService->getRecurringEventInstances($user, $seriesMasterId, $startDate, $endDate);
            
            $appointmentsCreated = 0;
            foreach ($instances as $instance) {
                $instanceEventId = $instance['id'] ?? null;
                if (!$instanceEventId) {
                    continue;
                }
                
                // Prüfe ob Appointment für diese Instanz bereits existiert (über microsoft_event_id)
                $existingAppointment = Appointment::where('microsoft_event_id', $instanceEventId)->first();
                if ($existingAppointment) {
                    continue; // Bereits vorhanden
                }
                
                // Parse Datum/Zeit der Instanz
                $instanceStart = Carbon::parse($instance['start']['dateTime']);
                $instanceEnd = Carbon::parse($instance['end']['dateTime']);
                
                // Nur zukünftige Instanzen
                if ($instanceStart->isPast()) {
                    continue;
                }
                
                // Prüfe zusätzlich ob bereits ein Appointment für Meeting+User existiert (für Sicherheit)
                // Das sollte eigentlich nicht passieren, aber als Fallback
                $existingAppointmentForMeeting = Appointment::where('meeting_id', $meeting->id)
                    ->where('user_id', $user->id)
                    ->where('start_date', $instanceStart->format('Y-m-d H:i:s'))
                    ->first();
                
                if ($existingAppointmentForMeeting) {
                    // Appointment existiert bereits - aktualisiere microsoft_event_id falls leer
                    if (!$existingAppointmentForMeeting->microsoft_event_id) {
                        $existingAppointmentForMeeting->update([
                            'microsoft_event_id' => $instanceEventId,
                            'sync_status' => 'synced',
                            'last_synced_at' => now(),
                        ]);
                    }
                    continue;
                }
                
                // Teams Links aus Instanz extrahieren (falls vorhanden)
                $instanceOnlineMeeting = $instance['onlineMeeting'] ?? null;
                $instanceTeamsJoinUrl = $instanceOnlineMeeting['joinUrl'] ?? null;
                $instanceTeamsWebUrl = $instanceOnlineMeeting['joinWebUrl'] ?? $instanceOnlineMeeting['url'] ?? null;
                
                // Erstelle Appointment für diese Instanz
                // WICHTIG: Für Recurring Events verwenden wir microsoft_event_id als eindeutigen Schlüssel
                // (nicht meeting_id+user_id, da mehrere Instanzen das gleiche Meeting haben können)
                try {
                    Appointment::firstOrCreate(
                        [
                            'microsoft_event_id' => $instanceEventId,
                        ],
                        [
                            'meeting_id' => $meeting->id,
                            'user_id' => $user->id,
                            'team_id' => $user->currentTeam->id ?? null,
                            'start_date' => $instanceStart,
                            'end_date' => $instanceEnd,
                            'location' => $meeting->location,
                            'microsoft_teams_join_url' => $instanceTeamsJoinUrl, // Instanz-spezifischer Link (falls vorhanden)
                            'microsoft_teams_web_url' => $instanceTeamsWebUrl, // Instanz-spezifischer Link (falls vorhanden)
                            'sync_status' => 'synced',
                            'last_synced_at' => now(),
                        ]
                    );
                    $appointmentsCreated++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Falls doch ein Duplikat auftritt (z.B. durch Race Condition), loggen und überspringen
                    if ($e->getCode() == 23000) { // Integrity constraint violation
                        Log::warning('Duplicate appointment detected (race condition?)', [
                            'meeting_id' => $meeting->id,
                            'user_id' => $user->id,
                            'microsoft_event_id' => $instanceEventId,
                        ]);
                        continue;
                    }
                    throw $e;
                }
                
                $appointmentsCreated++;
            }
            
            return [
                'created' => $appointmentsCreated > 0,
                'meetings' => 0, // Meeting wurde bereits erstellt oder existierte bereits
                'appointments' => $appointmentsCreated,
                'title' => $event['subject'] ?? '',
            ];
        } else {
            // Einzelnes Event: Meeting + Appointment wie bisher
            $result = $this->createMeetingFromEvent($user, $event, null, false);
            
            return [
                'created' => $result['meeting'] !== null,
                'meetings' => $result['meeting'] ? 1 : 0,
                'appointments' => $result['appointments'],
                'title' => $event['subject'] ?? '',
            ];
        }
    }

    /**
     * Erstellt ein Meeting für eine Serie (Elternelement)
     */
    protected function createSeriesMeeting(User $user, array $event, string $seriesMasterId): ?Meeting
    {
        $teamId = $user->currentTeam->id ?? null;
        
        // Recurrence Pattern extrahieren
        $recurrence = $event['recurrence'] ?? null;
        $recurrenceType = null;
        $recurrenceInterval = null;
        $recurrenceDaysOfWeek = null;
        $recurrenceStartDate = null;
        $recurrenceEndDate = null;
        
        if ($recurrence) {
            $pattern = $recurrence['pattern'] ?? [];
            $range = $recurrence['range'] ?? [];
            
            $recurrenceType = strtolower($pattern['type'] ?? '');
            $recurrenceInterval = $pattern['interval'] ?? 1;
            $recurrenceDaysOfWeek = $pattern['daysOfWeek'] ?? null;
            
            if (!empty($range['startDate'])) {
                $recurrenceStartDate = Carbon::parse($range['startDate'])->toDateString();
            }
            if (!empty($range['endDate'])) {
                $recurrenceEndDate = Carbon::parse($range['endDate'])->toDateString();
            }
        }
        
        // Teams Meeting Link extrahieren
        $onlineMeeting = $event['onlineMeeting'] ?? null;
        $teamsJoinUrl = $onlineMeeting['joinUrl'] ?? null;
        $teamsWebUrl = $onlineMeeting['joinWebUrl'] ?? $onlineMeeting['url'] ?? null;
        
        // Meeting für die Serie erstellen (OHNE konkrete Datum/Zeit - die kommen in Appointments)
        $meeting = Meeting::create([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'title' => $event['subject'] ?? 'Ohne Titel',
            'description' => $event['body']['content'] ?? $event['bodyPreview'] ?? null,
            'location' => $event['location']['displayName'] ?? $event['location']['locationUri'] ?? null,
            'status' => 'planned',
            'microsoft_series_master_id' => $seriesMasterId,
            'is_series_instance' => false, // Das Meeting selbst ist KEINE Instanz, sondern die Serie
            'microsoft_online_meeting_id' => $event['onlineMeetingId'] ?? $onlineMeeting['id'] ?? null,
            'microsoft_teams_join_url' => $teamsJoinUrl,
            'microsoft_teams_web_url' => $teamsWebUrl,
            // Recurrence Pattern
            'recurrence_type' => $recurrenceType,
            'recurrence_interval' => $recurrenceInterval,
            'recurrence_days_of_week' => $recurrenceDaysOfWeek,
            'recurrence_start_date' => $recurrenceStartDate,
            'recurrence_end_date' => $recurrenceEndDate,
        ]);
        
        // Organizer als Participant hinzufügen
        MeetingParticipant::firstOrCreate(
            [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'organizer',
                'response_status' => 'accepted',
            ]
        );
        
        return $meeting;
    }

    protected function createMeetingFromEvent(User $user, array $event, ?string $seriesMasterId = null, bool $isSeriesInstance = false): array
    {
        $eventId = $event['id'] ?? null;
        if (!$eventId) {
            return ['meeting' => null, 'appointments' => 0];
        }

        // Für einzelne Events: Prüfe ob Appointment bereits existiert (über microsoft_event_id)
        // microsoft_event_id ist eindeutig und team-übergreifend
        $existingAppointment = Appointment::where('microsoft_event_id', $eventId)->first();
        if ($existingAppointment) {
            return ['meeting' => $existingAppointment->meeting, 'appointments' => 0];
        }
        
        // Prüfe ob Meeting bereits existiert (für einzelne Events über microsoft_event_id)
        $existingMeeting = Meeting::where('microsoft_event_id', $eventId)->first();
        if ($existingMeeting) {
            // Meeting existiert - prüfe ob Appointment bereits existiert
            $startDateTime = Carbon::parse($event['start']['dateTime']);
            $endDateTime = Carbon::parse($event['end']['dateTime']);
            
            $existingAppointment = Appointment::where('microsoft_event_id', $eventId)
                ->orWhere(function($query) use ($existingMeeting, $user, $startDateTime) {
                    $query->where('meeting_id', $existingMeeting->id)
                          ->where('user_id', $user->id)
                          ->where('start_date', $startDateTime->format('Y-m-d H:i:s'));
                })
                ->first();
            
            if (!$existingAppointment) {
                // Erstelle Appointment für User
                $teamId = $user->currentTeam->id ?? null;
                
            // Teams Links aus Event extrahieren
            $onlineMeeting = $event['onlineMeeting'] ?? null;
            $teamsJoinUrl = $onlineMeeting['joinUrl'] ?? null;
            $teamsWebUrl = $onlineMeeting['joinWebUrl'] ?? $onlineMeeting['url'] ?? null;
            
            Appointment::create([
                'meeting_id' => $existingMeeting->id,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'location' => $existingMeeting->location,
                'microsoft_event_id' => $eventId,
                'microsoft_teams_join_url' => $teamsJoinUrl,
                'microsoft_teams_web_url' => $teamsWebUrl,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);
            }
            
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
            // Recurrence Pattern extrahieren
            $recurrence = $event['recurrence'] ?? null;
            $recurrenceType = null;
            $recurrenceInterval = null;
            $recurrenceDaysOfWeek = null;
            $recurrenceStartDate = null;
            $recurrenceEndDate = null;
            
            if ($recurrence) {
                $pattern = $recurrence['pattern'] ?? [];
                $range = $recurrence['range'] ?? [];
                
                $recurrenceType = strtolower($pattern['type'] ?? '');
                $recurrenceInterval = $pattern['interval'] ?? 1;
                $recurrenceDaysOfWeek = $pattern['daysOfWeek'] ?? null;
                
                if (!empty($range['startDate'])) {
                    $recurrenceStartDate = Carbon::parse($range['startDate'])->toDateString();
                }
                if (!empty($range['endDate'])) {
                    $recurrenceEndDate = Carbon::parse($range['endDate'])->toDateString();
                }
            }
            
            // Teams Meeting Link extrahieren
            $onlineMeeting = $event['onlineMeeting'] ?? null;
            $teamsJoinUrl = $onlineMeeting['joinUrl'] ?? null;
            $teamsWebUrl = $onlineMeeting['joinWebUrl'] ?? $onlineMeeting['url'] ?? null;
            
            // Meeting erstellen (ohne konkrete Daten - die kommen in Appointments)
            // Für einzelne Events: microsoft_event_id setzen
            // Für Serien: KEIN microsoft_event_id (nur microsoft_series_master_id)
            $meetingData = [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'title' => $event['subject'] ?? 'Ohne Titel',
                'description' => $event['body']['content'] ?? $event['bodyPreview'] ?? null,
                'location' => $event['location']['displayName'] ?? $event['location']['locationUri'] ?? null,
                'status' => 'planned',
                'microsoft_series_master_id' => $seriesMasterId ?? $event['seriesMasterId'] ?? null,
                'is_series_instance' => false, // Meeting ist die Serie, keine Instanz
                'microsoft_online_meeting_id' => $event['onlineMeetingId'] ?? $onlineMeeting['id'] ?? null,
                'microsoft_teams_join_url' => $teamsJoinUrl,
                'microsoft_teams_web_url' => $teamsWebUrl,
            ];
            
            // Nur für einzelne Events: microsoft_event_id setzen
            if (!$seriesMasterId && !$isSeriesInstance) {
                $meetingData['microsoft_event_id'] = $eventId;
            }
            
            // Recurrence Pattern nur für Serien
            if ($seriesMasterId || $recurrenceType) {
                $meetingData['recurrence_type'] = $recurrenceType;
                $meetingData['recurrence_interval'] = $recurrenceInterval;
                $meetingData['recurrence_days_of_week'] = $recurrenceDaysOfWeek;
                $meetingData['recurrence_start_date'] = $recurrenceStartDate;
                $meetingData['recurrence_end_date'] = $recurrenceEndDate;
            }
            
            $meeting = Meeting::create($meetingData);

            // Organizer als Participant hinzufügen
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => 'organizer',
                'response_status' => 'accepted',
            ]);

            // Appointment für Organizer erstellen (mit konkreten Daten)
            Appointment::firstOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $user->id,
                ],
                [
                    'team_id' => $teamId,
                    'start_date' => $startDateTime,
                    'end_date' => $endDateTime,
                    'location' => $meeting->location,
                    'microsoft_event_id' => $eventId,
                    'microsoft_teams_join_url' => $teamsJoinUrl, // Teams Link im Appointment
                    'microsoft_teams_web_url' => $teamsWebUrl, // Teams Link im Appointment
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]
            );

            // Teilnehmer aus Event holen und als Participants/Appointments hinzufügen
            $attendees = $event['attendees'] ?? [];
            $appointmentsCreated = 1; // Organizer bereits gezählt

            foreach ($attendees as $attendee) {
                $attendeeEmail = $attendee['emailAddress']['address'] ?? null;
                $attendeeName = $attendee['emailAddress']['name'] ?? $attendeeEmail;
                
                if (!$attendeeEmail) {
                    continue;
                }

                // User anhand Email finden
                $attendeeUser = User::where('email', $attendeeEmail)->first();
                
                if ($attendeeUser) {
                    // Interner User: Participant + Appointment erstellen
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

                    // Appointment erstellen (mit konkreten Daten)
                    $appointment = Appointment::firstOrCreate(
                        [
                            'meeting_id' => $meeting->id,
                            'user_id' => $attendeeUser->id,
                        ],
                        [
                            'team_id' => $teamId,
                            'start_date' => $startDateTime,
                            'end_date' => $endDateTime,
                            'location' => $meeting->location,
                            'microsoft_event_id' => $eventId,
                            'sync_status' => 'synced',
                            'last_synced_at' => now(),
                        ]
                    );

                    if ($appointment->wasRecentlyCreated) {
                        $appointmentsCreated++;
                    }
                } else {
                    // Externer User (ohne Account): Nur als Participant hinzufügen (ohne Appointment)
                    MeetingParticipant::firstOrCreate(
                        [
                            'meeting_id' => $meeting->id,
                            'email' => $attendeeEmail,
                        ],
                        [
                            'name' => $attendeeName,
                            'role' => $attendee['type'] === 'required' ? 'attendee' : 'optional',
                            'response_status' => $this->mapResponseStatus($attendee['status']['response'] ?? 'none'),
                            'microsoft_attendee_id' => $attendee['id'] ?? null,
                        ]
                    );
                }
            }

            // organizerAppointment wurde bereits oben erstellt und gezählt

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
                'trace' => $e->getTraceAsString(),
            ]);
            return ['meeting' => null, 'appointments' => 0];
        }
    }

    /**
     * Synchronisiert Teilnehmer aus einem Event (für interne und externe User)
     */
    protected function syncParticipantsFromEvent(Meeting $meeting, array $event): void
    {
        $attendees = $event['attendees'] ?? [];
        
        foreach ($attendees as $attendee) {
            $attendeeEmail = $attendee['emailAddress']['address'] ?? null;
            $attendeeName = $attendee['emailAddress']['name'] ?? $attendeeEmail;
            
            if (!$attendeeEmail) {
                continue;
            }

            // User anhand Email finden
            $attendeeUser = User::where('email', $attendeeEmail)->first();
            
            if ($attendeeUser) {
                // Interner User: Participant erstellen
                MeetingParticipant::firstOrCreate(
                    [
                        'meeting_id' => $meeting->id,
                        'user_id' => $attendeeUser->id,
                    ],
                    [
                        'role' => $attendee['type'] === 'required' ? 'attendee' : 'optional',
                        'response_status' => $this->mapResponseStatus($attendee['status']['response'] ?? 'none'),
                        'microsoft_attendee_id' => $attendee['id'] ?? null,
                    ]
                );
            } else {
                // Externer User (ohne Account): Nur als Participant hinzufügen
                MeetingParticipant::firstOrCreate(
                    [
                        'meeting_id' => $meeting->id,
                        'email' => $attendeeEmail,
                    ],
                    [
                        'name' => $attendeeName,
                        'role' => $attendee['type'] === 'required' ? 'attendee' : 'optional',
                        'response_status' => $this->mapResponseStatus($attendee['status']['response'] ?? 'none'),
                        'microsoft_attendee_id' => $attendee['id'] ?? null,
                    ]
                );
            }
        }
    }
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

