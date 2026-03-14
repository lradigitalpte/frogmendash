<?php

namespace Webkul\RovInspection;

use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RovInspectionServiceProvider extends ServiceProvider
{
    public static string $name = 'rov-inspection';

    public function boot(): void
    {
        Route::get('/report/{hash}', [Http\Controllers\ClientReportController::class, 'show'])
            ->name('rov-inspection.report.client')
            ->middleware(['web']);

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(RovInspectionPlugin::make());
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rov-inspection');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rov-inspection');
    }

    public function register(): void
    {
        // Service provider registration
    }
}
