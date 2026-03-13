<?php

namespace Webkul\Warranty;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

class WarrantyPlugin implements Plugin
{
    public function getId(): string
    {
        return 'warranty';
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
                ->discoverResources(
                    in: __DIR__.'/Filament/Resources',
                    for: 'Webkul\\Warranty\\Filament\\Resources'
                )
                ->discoverPages(
                    in: __DIR__.'/Filament/Pages',
                    for: 'Webkul\\Warranty\\Filament\\Pages'
                );
        });
    }

    public function boot(Panel $panel): void {}
}
