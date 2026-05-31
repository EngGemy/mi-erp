<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Services\CatalogApplyService;
use App\Support\ProjectDefaultVariables;
use Illuminate\Database\Seeder;

/**
 * مشروع "كراون - تسمين" - مطابق لملف الإكسل الأصلي.
 */
class CrownProjectSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        $project = Project::updateOrCreate(
            ['code' => 'CROWN-FATTEN'],
            [
                'name' => 'كراون - تسمين',
                'description' => 'نظام حصر عنابر تسمين دواجن - كراون (تجميع آلي 4 ماتور)',
                'default_scrap_percent' => 1,
                'units_multiplier' => 2,
                'is_active' => true,
            ]
        );

        ProjectDefaultVariables::syncToProject($project, [
            'tiers' => 4,
            'lines' => 5,
            'cages' => 118,
            'cage'  => 1,
        ]);

        app(CatalogApplyService::class)->applyFromCatalog(
            $project,
            CatalogApplyService::MODE_REPLACE
        );
    }
}
