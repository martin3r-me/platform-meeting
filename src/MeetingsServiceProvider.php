<?php

namespace Platform\Meetings;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\MeetingSeries;
use Platform\Meetings\Policies\MeetingPolicy;
use Platform\Meetings\Policies\MeetingSeriesPolicy;

/**
 * Meetings — headless (Phase 1 der Modul-Auflösung).
 *
 * Die bedienbare App (Nav-Eintrag, Seiten, Routen, Livewire, 7 MCP-Tools) ist
 * entfernt: ein Termin lebt künftig allein im Eingang (home); Ziel ist
 * "Termin = Inbox-Item". Was HIER bleibt, ist reiner Backend-Motor:
 *   - Morph-Alias `meeting` + `Meeting`-Model (Alt-Daten der Ist-Zeit),
 *   - `MeetingsEntityLinkProvider` (Zeit-Cascade in Phase 2 abgeschaltet, nur
 *     noch Metriken/Verortung bis zur finalen Migration),
 *   - `MeetingPromotionService`, `GenerateRecurringMeetings`, Models, Policies.
 *
 * Phase 2 hat die Ist-Zeit auf den `inbox_item`-Link umgehängt; sobald die
 * Migration (`inbox:migrate-meeting-time-to-item`) produktiv verifiziert ist,
 * fällt dieser Motor (Model + Morph) weg und das Paket verschwindet ganz (Phase 3).
 */
class MeetingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/meetings.php', 'meetings');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Meetings\Console\Commands\GenerateRecurringMeetings::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Meeting als linkbares Objekt am Org-Baum (Alt-Daten / Verortung).
        Relation::morphMap([
            'meeting' => Meeting::class,
        ]);

        // Meeting-Instanz als Wissens-Quelle am Knoten registrieren.
        // Soft-gekoppelt: ohne Organization-Modul No-op.
        try {
            if (class_exists(\Platform\Organization\Services\EntityLinkRegistry::class)) {
                resolve(\Platform\Organization\Services\EntityLinkRegistry::class)
                    ->register(new \Platform\Meetings\Organization\MeetingsEntityLinkProvider());
            }
        } catch (\Throwable $e) {
            // Organization-Modul nicht geladen — Meeting-Wissen aggregiert dann nicht.
        }

        // Schema bleibt (Model wird weiter gelesen).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Model-/Promotion-Autorisierung.
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Meeting::class => MeetingPolicy::class,
            MeetingSeries::class => MeetingSeriesPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
}
