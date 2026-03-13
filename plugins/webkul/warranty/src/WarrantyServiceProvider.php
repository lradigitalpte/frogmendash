<?php

namespace Webkul\Warranty;

use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class WarrantyServiceProvider extends PackageServiceProvider
{
    public static string $name = 'warranty';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2026_03_08_100001_create_warranty_policies_table',
                '2026_03_08_100002_create_warranties_table',
                '2026_03_08_100003_add_warranty_policy_id_to_products_table',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->icon('warranty');
    }

    public function packageBooted(): void {}

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(WarrantyPlugin::make());
        });
    }
}
