<?php

namespace Webkul\RovInspection;

use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class RovInspectionServiceProvider extends PackageServiceProvider
{
    public static string $name = 'rov-inspection';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                '2026_03_08_000001_create_rov_projects_table',
                '2026_03_08_000002_create_inspection_points_table',
                '2026_03_08_000003_create_inspection_media_table',
                '2026_03_08_000004_create_inspection_reports_table',
                '2026_03_08_000005_create_report_access_logs_table',
                '2026_03_08_000006_create_project_structures_table',
                '2026_03_08_000007_create_inspection_views_table',
                '2026_03_08_000008_update_rov_projects_add_gps_and_plan_view',
                '2026_03_08_000009_update_inspection_points_for_views',
                '2026_03_08_000010_update_inspection_media_for_structures',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->icon('rov-inspection');
    }

    public function packageBooted(): void
    {
        Route::get('/report/{hash}', [\Webkul\RovInspection\Http\Controllers\ClientReportController::class, 'show'])
            ->name('rov-inspection.report.client')
            ->middleware(['web']);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(RovInspectionPlugin::make());
        });
    }
}
