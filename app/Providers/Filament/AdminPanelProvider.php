<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditCrownProfile;
use App\Filament\Pages\CrownDashboard;
use App\Filament\Pages\ManageGeneralSettings;
use App\Filament\Pages\WarehouseDashboard;
use App\Http\Middleware\ApplyCrownTheme;
use App\Services\CrownSettings;
use App\Services\CrownThemeResolver;
use App\Support\CrownThemePalettes;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditCrownProfile::class, isSimple: false)
            ->darkMode()
            ->defaultThemeMode(ThemeMode::System)
            ->brandName(fn () => (string) $this->safeSetting('factory_name', 'Crown ERP'))
            ->brandLogo(fn () => $this->resolveBrandLogo())
            ->brandLogoHeight('2.25rem')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors(fn () => CrownThemePalettes::filamentColorSet(CrownThemeResolver::resolveColorKey()))
            ->font(
                'Cairo',
                url: 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): HtmlString => new HtmlString(
                    view('filament.components.crown-theme-boot')->render()
                ),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    view('filament.components.crown-theme-head')->render()
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                CrownDashboard::class,
                ManageGeneralSettings::class,
                WarehouseDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ApplyCrownTheme::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationLabel('الأدوار والصلاحيات')
                    ->navigationGroup('الإدارة')
                    ->navigationSort(99),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function resolveBrandLogo(): string
    {
        $path = $this->safeSetting('logo_path');

        if (! is_string($path) || $path === '') {
            return asset('images/mi_logo.svg');
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // Legacy uploads saved to the default private disk before disk('public') was set.
        if (Storage::disk('local')->exists($path)) {
            return route('crown.private-file', ['path' => $path]);
        }

        return asset('images/mi_logo.svg');
    }

    protected function safeSetting(string $key, mixed $default = null): mixed
    {
        try {
            return CrownSettings::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
