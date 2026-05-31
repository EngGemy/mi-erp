<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\ItemRecipe;
use App\Models\RawMaterial;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $rawWh = Warehouse::updateOrCreate(
            ['name' => 'مخزن الخام', 'type' => Warehouse::TYPE_RAW],
            ['location' => 'المصنع — خام', 'is_active' => true]
        );

        $finishedWh = Warehouse::updateOrCreate(
            ['name' => 'مخزن التام', 'type' => Warehouse::TYPE_FINISHED],
            ['location' => 'المصنع — تام', 'is_active' => true]
        );

        $steel = RawMaterial::updateOrCreate(
            ['code' => 'steel'],
            ['name' => 'حديد', 'unit' => 'كجم', 'is_active' => true]
        );

        $stock = app(StockService::class);
        $balance = $stock->getBalance($rawWh, $steel);
        if ($balance->qty_on_hand < 1000) {
            $balance->update(['qty_on_hand' => 1000]);
        }

        $legPost = CatalogItem::query()->where('code', 'leg_post')->first();

        if ($legPost) {
            ItemRecipe::updateOrCreate(
                [
                    'catalog_item_id' => $legPost->id,
                    'raw_material_id' => $steel->id,
                ],
                ['qty_per_unit' => 2]
            );
        }
    }
}
