<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Get logo from settings
        $lightLogo = Setting::get('site_logo_full_lite');
        $darkLogo = Setting::get('site_logo_full_dark');
        $favicon = Setting::get('site_favicon');
        $brandName = Setting::get('site_name', 'Dashboard');
        
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->darkMode(true)
            ->brandName($brandName)
            ->brandLogo($lightLogo ? asset('storage/' . $lightLogo) : null)
            ->darkModeBrandLogo($darkLogo ? asset('storage/' . $darkLogo) : null)
            ->brandLogoHeight('2.5rem')
            ->favicon($favicon ? asset('storage/' . $favicon) : null)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                'Subscription Management',
                'Payments',
                'Notifications',
                'Location Management',
                'Business Data',
                'Access Control',
                'System',
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->defaultAvatarProvider(\Filament\AvatarProviders\UiAvatarsProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\FinancialSummaryWidget::class,
                \App\Filament\Widgets\UsersChart::class,
                \App\Filament\Widgets\ActivityChart::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
