<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $websiteEnabled = (bool) config('features.website.enabled');

        return $panel
            ->id('customer')
            ->path($websiteEnabled ? '/' : '__customer')
            ->homeUrl($websiteEnabled ? url('/') : url('/admin/login'))
            ->authPasswordBroker('customers')
            ->profile(isSimple: false)
            ->favicon(asset('images/logo.png'))
            ->brandLogo(asset('images/logo.png'))
            ->darkMode(false)
            ->brandLogoHeight('3.5rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->topNavigation()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('customer');
    }
}
