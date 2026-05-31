<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Stage;
use Illuminate\Support\Facades\Cache;

class CrownSettings
{
    protected const CACHE_KEY = 'crown_settings_all';

    public static function defaults(): array
    {
        return [
            'factory_name'              => 'مصنع Crown',
            'logo_path'                 => null,
            'default_scrap_percent'     => (float) config('bom.default_scrap_percent', 1),
            'default_units_multiplier'  => 2.0,
            'currency'                  => 'EGP',
            'default_rounding'          => config('bom.default_rounding', 'up'),
            'default_theme_color'       => \App\Support\CrownThemePalettes::DEFAULT_KEY,
        ];
    }

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $stored = Setting::query()->pluck('value', 'key')->map(fn ($v) => $v)->all();

            return array_merge(self::defaults(), $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return $all[$key] ?? $default ?? (self::defaults()[$key] ?? null);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'stages') {
                continue;
            }
            self::set($key, $value);
        }
        Cache::forget(self::CACHE_KEY);
    }

    public static function defaultScrapPercent(): float
    {
        return (float) self::get('default_scrap_percent', 1);
    }

    public static function defaultUnitsMultiplier(): float
    {
        return (float) self::get('default_units_multiplier', 2);
    }

    public static function defaultRounding(): string
    {
        return (string) self::get('default_rounding', 'up');
    }

    /**
     * @param  array<int, array{name: string, sort: int, weight: float}>  $stages
     */
    public static function syncStages(array $stages): void
    {
        foreach ($stages as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $existing = isset($row['id'])
                ? Stage::find($row['id'])
                : Stage::where('name', $row['name'])->first();

            if ($existing) {
                $existing->update([
                    'name'   => $row['name'],
                    'sort'   => (int) ($row['sort'] ?? $existing->sort),
                    'weight' => (float) ($row['weight'] ?? $existing->weight),
                ]);
            } else {
                Stage::create([
                    'name'   => $row['name'],
                    'sort'   => (int) ($row['sort'] ?? 0),
                    'weight' => (float) ($row['weight'] ?? 0),
                ]);
            }
        }
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, array{id: int, name: string, sort: int, weight: float}>
     */
    public static function stagesForForm(): array
    {
        return Stage::orderBy('sort')->get()->map(fn (Stage $s) => [
            'id'     => $s->id,
            'name'   => $s->name,
            'sort'   => $s->sort,
            'weight' => (float) $s->weight,
        ])->all();
    }

    public static function projectDefaults(): array
    {
        return [
            'default_scrap_percent' => self::defaultScrapPercent(),
            'units_multiplier'      => self::defaultUnitsMultiplier(),
        ];
    }
}
