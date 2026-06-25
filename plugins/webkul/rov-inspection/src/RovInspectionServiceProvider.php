<?php

namespace Webkul\RovInspection;

use Filament\Panel;
use Filament\Support\Facades\FilamentView;
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
        Route::get('/report/{hash}', [Http\Controllers\ClientReportController::class, 'show'])
            ->name('rov-inspection.report.client')
            ->middleware(['web']);

        Route::get('/report/{hash}/data', [Http\Controllers\ClientReportController::class, 'data'])
            ->name('rov-inspection.report.client.data')
            ->middleware(['web']);

        Route::get('/report/{hash}/download/pdf', [Http\Controllers\ClientReportController::class, 'downloadPdf'])
            ->name('rov-inspection.report.client.download-pdf')
            ->middleware(['web']);

        // Direct browser -> S3 multipart upload signing endpoints (auth-only).
        Route::prefix('admin/rov-inspection/s3-multipart')
            ->name('rov-inspection.s3-multipart.')
            ->middleware(['web', 'auth'])
            ->controller(Http\Controllers\S3MultipartUploadController::class)
            ->group(function () {
                Route::post('create', 'create')->name('create');
                Route::post('sign', 'signPart')->name('sign');
                Route::post('complete', 'complete')->name('complete');
                Route::post('abort', 'abort')->name('abort');
            });

        // Make the uploader's Alpine factory available on every panel page.
        FilamentView::registerRenderHook(
            'panels::body.end',
            fn (): string => view('rov-inspection::forms.components.s3-multipart-upload-script')->render(),
        );
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(RovInspectionPlugin::make());
        });
    }
}
