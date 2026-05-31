<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * مجموعات ألوان جاهزة — هادئة ومريحة (50→950) للثيم.
 */
class CrownThemePalettes
{
    public const DEFAULT_KEY = 'calm_red';

    /** لون التنبيه/النقص — ثابت من الشعار، لا يتبع الثيم الأساسي */
    public const ALERT_DANGER = '#e02424';

    /**
     * @return array<string, array{label: string, base: string, description: string}>
     */
    public static function presets(): array
    {
        return [
            'calm_red' => [
                'label'       => 'أحمر هادئ',
                'description' => 'مشتق من الشعار — للعناصر الرئيسية',
                'base'        => '#c53030',
            ],
            'calm_blue' => [
                'label'       => 'أزرق هادئ',
                'description' => 'أزرق مائل للرمادي',
                'base'        => '#4a7ab8',
            ],
            'teal' => [
                'label'       => 'أخضر تيل',
                'description' => 'تيل هادئ',
                'base'        => '#0f9d8f',
            ],
            'warm_gray' => [
                'label'       => 'رمادي دافئ',
                'description' => 'محايد مريح',
                'base'        => '#64748b',
            ],
            'amber' => [
                'label'       => 'عنبري',
                'description' => 'ذهبي هادئ',
                'base'        => '#c68a2e',
            ],
            'calm_purple' => [
                'label'       => 'بنفسجي هادئ',
                'description' => 'بنفسجي باهت',
                'base'        => '#7c6ba8',
            ],
        ];
    }

    public static function isValid(?string $key): bool
    {
        return $key !== null && $key !== '' && array_key_exists($key, self::presets());
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(bool $includeSystemDefault = false): array
    {
        $options = [];
        if ($includeSystemDefault) {
            $options[''] = 'افتراضي النظام';
        }
        foreach (self::presets() as $key => $preset) {
            $options[$key] = $preset['label'];
        }

        return $options;
    }

    /**
     * @return array<int | string, string | int>
     */
    public static function filamentPrimary(?string $key): array
    {
        $key = self::isValid($key) ? $key : self::DEFAULT_KEY;

        return Color::generatePalette(self::presets()[$key]['base']);
    }

    /**
     * متغيرات CSS للجداول المخصصة — تتغير مع اختيار اللون.
     *
     * @return array<string, string>
     */
    public static function cssVariables(?string $key): array
    {
        $palette = self::filamentPrimary($key);

        return [
            '--crown-primary'       => $palette[600] ?? $palette[500],
            '--crown-primary-dark'  => $palette[700] ?? $palette[600],
            '--crown-primary-light' => $palette[400] ?? $palette[500],
            '--crown-sec-bg'        => $palette[50],
            '--crown-sec-text'      => $palette[800],
            '--crown-danger'        => self::ALERT_DANGER,
            '--crown-charcoal'      => '#2b2d33',
            '--crown-charcoal-soft' => '#3a3d44',
            '--crown-bg'            => '#f4f5f7',
            '--crown-card'          => '#ffffff',
            '--crown-border'        => '#e6e8ec',
            '--crown-grid'          => '#eef0f2',
            '--crown-zebra'         => '#f7f8fa',
            '--crown-text'          => '#1f2937',
            '--crown-text-muted'    => '#6b7280',
            '--crown-success'       => '#15803d',
            '--crown-warning'       => '#b45309',
            '--crown-radius'        => '0.5rem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function cssVariablesDark(?string $key): array
    {
        $palette = self::filamentPrimary($key);

        return array_merge(self::cssVariables($key), [
            '--crown-sec-bg'        => $palette[950] ?? '#1f2937',
            '--crown-sec-text'      => $palette[200] ?? '#e5e7eb',
            '--crown-bg'            => '#1a1c20',
            '--crown-card'          => '#24262b',
            '--crown-border'        => '#3a3d44',
            '--crown-grid'          => '#33363d',
            '--crown-zebra'         => '#2b2d33',
            '--crown-text'          => '#e5e7eb',
            '--crown-text-muted'    => '#9ca3af',
        ]);
    }

    /**
     * @return array<string, array<int | string, string | int> | string>
     */
    public static function filamentColorSet(?string $key): array
    {
        return [
            'primary' => self::filamentPrimary($key),
            'success' => Color::generatePalette('#15803d'),
            'warning' => Color::generatePalette('#b45309'),
            'danger'  => Color::generatePalette(self::ALERT_DANGER),
            'gray'    => Color::generatePalette('#6b7280'),
        ];
    }
}
