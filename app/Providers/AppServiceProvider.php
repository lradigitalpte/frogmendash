<?php

namespace App\Providers;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Webkul\Security\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pin generated URLs to APP_URL. Needed because we trust all proxies
        // (Railway), which otherwise mis-derives host/port — e.g. dropping the
        // :8000 port for local Docker and redirecting to a refused port 80.
        $appUrl = config('app.url');

        if (is_string($appUrl) && $appUrl !== '') {
            URL::forceRootUrl($appUrl);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset
            ->columnSpanFull());

        Grid::configureUsing(fn (Grid $grid) => $grid
            ->columnSpanFull());

        Section::configureUsing(fn (Section $section) => $section
            ->columnSpanFull());

        // Ensure Alpine.js is loaded for Filament
        Blade::directive('filamentAssets', function () {
            return "@vite(['resources/css/app.css', 'resources/js/app.js'])";
        });
    }
}
