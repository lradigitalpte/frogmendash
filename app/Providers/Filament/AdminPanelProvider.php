<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Views\Components\Modal;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkul\PluginManager\Http\Middleware\EnsurePluginEnabledForCompany;
use Webkul\RovInspection\RovInspectionPlugin;
use Webkul\Security\Http\Middleware\EnsureAdminLoginNoRedirectLoop;
use Webkul\Support\Filament\Pages\Profile;
use Webkul\Support\GlobalSearchProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        set_time_limit(0); // Unlimited execution time for panel setup

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(false) // Tenants created by admins: Companies + Users (assign role so they can access panel)
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->unsavedChangesAlerts()
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->databaseNotifications()
            ->renderHook(
                'panels::layout.start',
                fn () => view('filament.layout-assets'),
            )
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label(fn () => filament()->auth()->user()?->name)
                    ->url(fn (): string => Profile::getUrl()),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('admin.navigation.dashboard'))
                    ->icon('icon-dashboard'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.contact'))
                    ->icon('icon-contacts'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.sale'))
                    ->icon('icon-sales'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.purchase'))
                    ->icon('icon-purchases'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.invoice'))
                    ->icon('icon-invoices'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.accounting'))
                    ->icon('icon-accounting'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.inventory'))
                    ->icon('icon-inventories'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.project'))
                    ->icon('icon-projects'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.rov-inspection'))
                    ->icon('heroicon-o-map'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.employee'))
                    ->icon('icon-employees'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.time-off'))
                    ->icon('icon-time-offs'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.recruitment'))
                    ->icon('icon-recruitments'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.website'))
                    ->icon('icon-website'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.plugin'))
                    ->icon('icon-plugin'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.setting'))
                    ->icon('icon-settings'),
            ])
            ->plugins([
                RovInspectionPlugin::make(),
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm'      => 2,
                    ]),
            ])
            ->globalSearch(provider: GlobalSearchProvider::class)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureAdminLoginNoRedirectLoop::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsurePluginEnabledForCompany::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ]);
    }
}
