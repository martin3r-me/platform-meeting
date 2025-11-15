# Meetings Modul

Modul für Meeting-Verwaltung mit Microsoft 365 Calendar Integration.

## Features

- ✅ Meeting-Verwaltung (erstellen, bearbeiten, löschen)
- ✅ Teilnehmer-Verwaltung mit RSVP-Status
- ✅ Agenda mit Kanban Board (analog zum Planner)
- ✅ Serientermine (Recurring Meetings)
- ✅ Microsoft 365 Calendar Integration
- ✅ Bidirektionaler RSVP-Sync (Outlook ↔ App)
- ✅ Webhook-Support für automatische Updates

## Installation

1. Modul in `composer.json` des Hauptprojekts registrieren:
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./platform/modules/meetings"
    }
  ],
  "require": {
    "martin3r/platform-meetings": "*"
  }
}
```

2. `composer update` ausführen

3. Migrationen ausführen:
```bash
php artisan migrate
```

4. Commands in `app/Console/Kernel.php` registrieren (für Scheduler):
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('meetings:generate-recurring')->daily();
    $schedule->command('meetings:renew-subscriptions')->daily();
}
```

## Microsoft 365 Integration

### 1. Scopes erweitern

In `platform/core/resources/views/components/teams-sso-script.blade.php`:

```javascript
microsoftTeams.authentication.getAuthToken({
    resources: [
        'https://graph.microsoft.com/User.Read',
        'https://graph.microsoft.com/Calendars.ReadWrite',
        'https://graph.microsoft.com/Calendars.ReadWrite.Shared',
        'https://graph.microsoft.com/OnlineMeetings.ReadWrite' // Optional für Teams-Meetings
    ],
    silent: true
})
```

### 2. Token-Speicherung

Der `MicrosoftGraphCalendarService` benötigt Access Tokens. Aktuell werden diese aus:
- Request Header `X-Microsoft-Access-Token`
- Session `microsoft_access_token_{user_id}`

**TODO**: Token-Persistierung implementieren:
- Option 1: `user_oauth_tokens` Tabelle
- Option 2: User-Model erweitern mit `microsoft_access_token` (encrypted)
- Option 3: Laravel Socialite Token-Speicherung nutzen

### 3. Webhook-Endpoint konfigurieren

Der Webhook-Endpoint ist: `/meetings/webhook/microsoft-calendar`

Stelle sicher, dass dieser Endpoint öffentlich erreichbar ist (für Microsoft Graph API).

## Verwendung

### Meeting erstellen

```php
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingParticipant;
use Platform\Meetings\Services\MicrosoftGraphCalendarService;

$meeting = Meeting::create([
    'title' => 'Team Meeting',
    'start_date' => now()->addDay(),
    'end_date' => now()->addDay()->addHour(),
    'location' => 'Konferenzraum A',
]);

// Teilnehmer hinzufügen
MeetingParticipant::create([
    'meeting_id' => $meeting->id,
    'user_id' => $userId,
    'role' => 'attendee',
]);

// Zu Microsoft Calendar syncen
$calendarService = app(MicrosoftGraphCalendarService::class);
$calendarService->createEvent($meeting);
```

### Serientermin erstellen

```php
use Platform\Meetings\Models\RecurringMeeting;

$recurring = RecurringMeeting::create([
    'title' => 'Wöchentliches Team Meeting',
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'recurrence_type' => 'weekly',
    'recurrence_interval' => 1,
    'next_meeting_date' => now()->next('Monday'),
]);

// Meetings werden automatisch durch Command generiert
```

### RSVP-Status synchronisieren

```php
$calendarService = app(MicrosoftGraphCalendarService::class);
$calendarService->syncParticipantResponses($meeting);
```

## Commands

- `meetings:generate-recurring` - Generiert Meetings aus Serienterminen (täglich ausführen)
- `meetings:renew-subscriptions` - Erneuert ablaufende Webhook-Subscriptions (täglich ausführen)

## Models

- `Meeting` - Haupt-Entity für Meetings
- `MeetingParticipant` - Teilnehmer mit RSVP-Status
- `MeetingAgendaItem` - Agenda-Items für Kanban Board
- `MeetingAgendaSlot` - Spalten für Kanban Board
- `RecurringMeeting` - Serientermine
- `Appointment` - User-spezifische Termine (für Kalender-Sync)
- `MicrosoftCalendarSubscription` - Webhook-Subscriptions

## Policies

- `MeetingPolicy` - Berechtigungen für Meetings
- `AppointmentPolicy` - Berechtigungen für Appointments

## TODO / Offene Punkte

1. **Token-Speicherung**: Access Tokens persistent speichern
2. **Scope-Erweiterung**: Scopes in Teams SSO Script erweitern
3. **Teams-Meetings**: Online-Meeting-Erstellung implementieren
4. **UI-Verbesserungen**: 
   - Agenda Item Bearbeitung
   - Teilnehmer-Verwaltung UI
   - Recurring Meeting UI
5. **Error Handling**: Besseres Error Handling für Graph API Calls
6. **Testing**: Unit Tests für Services

## Struktur

```
meetings/
├── composer.json
├── config/
│   └── meetings.php
├── database/
│   └── migrations/
├── resources/
│   └── views/
│       └── livewire/
├── routes/
│   ├── web.php
│   └── guest.php
└── src/
    ├── Console/
    │   └── Commands/
    ├── Http/
    │   └── Controllers/
    ├── Livewire/
    ├── Models/
    ├── Policies/
    ├── Services/
    └── MeetingsServiceProvider.php
```

