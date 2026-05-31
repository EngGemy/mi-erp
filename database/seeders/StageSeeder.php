<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'تصنيع', 'sort' => 1, 'weight' => 40],
            ['name' => 'توريد', 'sort' => 2, 'weight' => 30],
            ['name' => 'تركيب', 'sort' => 3, 'weight' => 30],
        ];

        foreach ($stages as $stage) {
            Stage::updateOrCreate(
                ['name' => $stage['name']],
                ['sort' => $stage['sort'], 'weight' => $stage['weight']]
            );
        }
    }
}
