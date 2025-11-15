<?php

namespace Platform\Meetings;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use Platform\Meetings\Models\Meeting;
use Platform\Meetings\Models\Appointment;
use Platform\Meetings\Policies\MeetingPolicy;
use Platform\Meetings\Policies\AppointmentPolicy;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MeetingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Meetings\Console\Commands\GenerateRecurringMeetings::class,
                \Platform\Meetings\Console\Commands\RenewCalendarSubscriptions::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Modul-Registrierung
        if (
            config()->has('meetings.routing') &&
            config()->has('meetings.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'meetings',
                'title'      => 'Meetings',
                'routing'    => config('meetings.routing'),
                'guard'      => config('meetings.guard'),
                'navigation' => config('meetings.navigation'),
                'sidebar'    => config('meetings.sidebar'),
                'billables'  => config('meetings.billables'),
            ]);
        }

        // Routen laden
        if (PlatformCore::getModule('meetings')) {
            ModuleRouter::group('meetings', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/guest.php');
            }, requireAuth: false);

            ModuleRouter::group('meetings', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });

            // Webhook Route (ohne Modul-Prefix)
            Route::post('/meetings/webhook/microsoft-calendar', 
                \Platform\Meetings\Http\Controllers\MicrosoftCalendarWebhookController::class
            )->name('meetings.webhook.microsoft-calendar');
        }

        // Config veröffentlichen & zusammenführen
        $this->publishes([
            __DIR__.'/../config/meetings.php' => config_path('meetings.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/meetings.php', 'meetings');

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'meetings');
        $this->registerLivewireComponents();

        // Policies registrieren
        $this->registerPolicies();
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Meetings\\Livewire';
        $prefix = 'meetings';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerPolicies(): void
    {
        $policies = [
            Meeting::class => MeetingPolicy::class,
            Appointment::class => AppointmentPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
}

