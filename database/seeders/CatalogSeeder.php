<?php

namespace Database\Seeders;

use App\Data\CrownTemplateData;
use App\Models\CatalogItem;
use App\Models\CatalogSection;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [];
        foreach (CrownTemplateData::sectionNames() as $i => $name) {
            $sections[$name] = CatalogSection::updateOrCreate(
                ['name' => $name],
                ['sort' => $i]
            )->id;
        }

        $sort = 0;
        foreach (CrownTemplateData::items() as $row) {
            [$code, $name, $sectionName, $length, $formula, $scrap] = $row;

            $scrapData = $this->parseScrap($scrap);

            CatalogItem::updateOrCreate(
                ['code' => $code],
                array_merge([
                    'catalog_section_id' => $sections[$sectionName] ?? $sections[array_key_first($sections)],
                    'name'               => $name,
                    'piece_length'       => $length,
                    'formula'            => $formula,
                    'rounding'           => 'up',
                    'sort'               => $sort++,
                    'is_active'          => true,
                ], $scrapData)
            );
        }
    }

    /**
     * @param  array{0: string, 1?: float|int}  $scrap
     * @return array<string, mixed>
     */
    protected function parseScrap(array $scrap): array
    {
        return match ($scrap[0]) {
            'percent' => [
                'scrap_mode'    => 'percent',
                'scrap_percent' => $scrap[1],
                'scrap_fixed'   => null,
            ],
            'fixed' => [
                'scrap_mode'    => 'fixed',
                'scrap_percent' => null,
                'scrap_fixed'   => $scrap[1],
            ],
            'none' => [
                'scrap_mode'    => 'none',
                'scrap_percent' => null,
                'scrap_fixed'   => null,
            ],
            default => [
                'scrap_mode'    => 'inherit',
                'scrap_percent' => null,
                'scrap_fixed'   => null,
            ],
        };
    }
}
