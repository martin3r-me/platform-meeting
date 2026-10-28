# platform-meetings — aufgelöst (Phase 3)

Dieses Modul wurde abgeschafft. Ziel erreicht: **„Termin = Inbox-Item"** — eine
Software, kein separates Meetings-Modul.

## Wohin ist alles gewandert?

- **Termin (Gesicht):** lebt im **Eingang** (`modules/home`, Channel `meeting`,
  Daten aus `services/user-connectors` → `user_connector_meeting_sessions`).
  Verknüpfen an einen Org-Knoten über das Verknüpfungen-Panel.
- **Ist-Zeit:** bucht auf das **Inbox-Item** (`context_type = InboxItem`), gebucht
  von `inbox:materialize-meeting-time`, auf den Knoten verteilt von
  `inbox:inherit-node-links` (Serie/Teilnehmer über `ical_uid`/`series_master_id`).
  Siehe `modules/inbox`.
- **Historische Meeting-Zeit:** einmalig migriert von
  `inbox:migrate-meeting-time-to-item` (context_type Meeting → InboxItem).

## Status

Leerer Grabstein (kein ServiceProvider, keine Klassen), damit die Instanz nicht
bricht, solange `martin3r/platform-meetings` noch in ihrer composer.json steht.
**To-do (manuell, Instanz):** `require` + `repositories`-Pfadeintrag entfernen,
`composer update`. Danach kann dieses Repo archiviert werden.

Die DB-Tabellen (`meetings_*`) bleiben als verwaiste Alt-Daten bestehen (harmlos).
