<?php

namespace Webkul\RovInspection;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Webkul\PluginManager\Package;
use Webkul\RovInspection\Filament\Pages\MapAnnotationPage;
use Webkul\RovInspection\Filament\Pages\RovInspectionPage;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class RovInspectionPlugin implements Plugin
{
    public function getId(): string
    {
        return 'rov-inspection';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel->when($panel->getId() === 'admin', function (Panel $panel): void {
            $panel
                ->pages([
                    RovInspectionPage::class,
                    MapAnnotationPage::class,
                ])
                ->resources([
                    RovProjectResource::class,
                    InspectionReportResource::class,
                ])
                ->navigationItems([
                    NavigationItem::make('rov-overview')
                        ->label('Overview')
                        ->url('/admin/rov-inspections')
                        ->group(__('admin.navigation.rov-inspection'))
                        ->icon('heroicon-o-home')
                        ->sort(1),
                    NavigationItem::make('rov-projects')
                        ->label('Inspection Projects')
                        ->url('/admin/rov-inspection/projects')
                        ->group(__('admin.navigation.rov-inspection'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->sort(2),
                    NavigationItem::make('rov-reports')
                        ->label('Reports')
                        ->url('/admin/rov-inspection/reports')
                        ->group(__('admin.navigation.rov-inspection'))
                        ->icon('heroicon-o-document-text')
                        ->sort(3),
                ]);
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
