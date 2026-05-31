<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectVariable;

class ProjectDefaultVariables
{
    /**
     * @return array<int, array{key: string, label: string, value: float|int, sort: int, field: string}>
     */
    public static function definitions(): array
    {
        return [
            ['key' => 'tiers', 'field' => 'var_tiers', 'label' => 'عدد الأدوار', 'value' => 4, 'sort' => 1],
            ['key' => 'lines', 'field' => 'var_lines', 'label' => 'عدد الخطوط', 'value' => 5, 'sort' => 2],
            ['key' => 'cages', 'field' => 'var_cages', 'label' => 'عدد العشوش أفقياً', 'value' => 118, 'sort' => 3],
            ['key' => 'cage', 'field' => 'var_cage', 'label' => 'العش', 'value' => 1, 'sort' => 4],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, value: float|int, sort: int}>
     */
    public static function all(): array
    {
        return array_map(
            fn (array $d) => [
                'key' => $d['key'],
                'label' => $d['label'],
                'value' => $d['value'],
                'sort' => $d['sort'],
            ],
            self::definitions()
        );
    }

    public static function defaultValue(string $key): float
    {
        foreach (self::definitions() as $def) {
            if ($def['key'] === $key) {
                return (float) $def['value'];
            }
        }

        return 0.0;
    }

    /**
     * @return array<string, float> key => value
     */
    public static function extractFromForm(array $data): array
    {
        $values = [];
        foreach (self::definitions() as $def) {
            $values[$def['key']] = isset($data[$def['field']])
                ? (float) $data[$def['field']]
                : (float) $def['value'];
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stripFromForm(array $data): array
    {
        foreach (self::definitions() as $def) {
            unset($data[$def['field']]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function mergeIntoForm(Project $project, array $data): array
    {
        $vars = $project->variables()->get()->keyBy('key');

        foreach (self::definitions() as $def) {
            $data[$def['field']] = (float) ($vars[$def['key']]->value ?? $def['value']);
        }

        return $data;
    }

    /**
     * @param  array<string, float>  $values  key => value
     */
    public static function syncToProject(Project $project, array $values): void
    {
        foreach (self::definitions() as $def) {
            ProjectVariable::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'key'        => $def['key'],
                ],
                [
                    'label' => $def['label'],
                    'value' => $values[$def['key']] ?? (float) $def['value'],
                    'sort'  => $def['sort'],
                ]
            );
        }
    }
}
