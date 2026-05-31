<?php

namespace App\Services;

use App\Models\User;
use App\Support\CrownThemePalettes;
use Filament\Support\Facades\FilamentColor;

class CrownThemeResolver
{
    public static function organizationColorKey(): string
    {
        $key = CrownSettings::get('default_theme_color', CrownThemePalettes::DEFAULT_KEY);

        return CrownThemePalettes::isValid($key) ? $key : CrownThemePalettes::DEFAULT_KEY;
    }

    public static function resolveColorKey(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user && CrownThemePalettes::isValid($user->theme_color)) {
            return $user->theme_color;
        }

        return self::organizationColorKey();
    }

    public static function resolveThemeMode(?User $user = null): string
    {
        $user ??= auth()->user();
        $mode = $user?->theme_mode ?? 'system';

        return in_array($mode, ['light', 'dark', 'system'], true) ? $mode : 'system';
    }

    public static function apply(?User $user = null): void
    {
        $colorKey = self::resolveColorKey($user);

        FilamentColor::register(CrownThemePalettes::filamentColorSet($colorKey));
    }

    /**
     * @return array{color_key: string, theme_mode: string, css_light: array<string, string>, css_dark: array<string, string>}
     */
    public static function viewData(?User $user = null): array
    {
        $colorKey = self::resolveColorKey($user);
        $themeMode = self::resolveThemeMode($user);

        return [
            'color_key'   => $colorKey,
            'theme_mode'  => $themeMode,
            'css_light'   => CrownThemePalettes::cssVariables($colorKey),
            'css_dark'    => CrownThemePalettes::cssVariablesDark($colorKey),
        ];
    }
}
