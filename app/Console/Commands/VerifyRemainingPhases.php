<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Services\BomEngine;
use App\Services\CrownSettings;
use App\Services\DashboardStatsService;
use App\Services\PurchaseOrderService;
use App\Services\StockService;
use Illuminate\Console\Command;

class VerifyRemainingPhases extends Command
{
    protected $signature = 'crown:verify-remaining';

    protected $description = 'اختبار قبول الإعدادات العامة والمشتريات ولوحة المؤشرات';

    public function handle(
        BomEngine $engine,
        StockService $stock,
        PurchaseOrderService $purchaseOrders,
        DashboardStatsService $dashboard,
    ): int {
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => 'InventorySeeder', '--force' => true]);

        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        if (! $project) {
            $this->error('مشروع CROWN-FATTEN غير موجود');

            return self::FAILURE;
        }

        $h = (float) (collect($engine->calculate($project->fresh('variables')))->firstWhere('code', 'leg_post')['total'] ?? 0);
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s → %s', $h, $okH ? 'OK' : 'FAIL'));

        CrownSettings::set('default_scrap_percent', 5.5);
        $defaults = CrownSettings::projectDefaults();
        $okScrap = abs($defaults['default_scrap_percent'] - 5.5) < 0.01;
        $this->line(sprintf('إعدادات الهالك 5.5%% → مشروع جديد %s → %s', $defaults['default_scrap_percent'], $okScrap ? 'OK' : 'FAIL'));

        $newProject = Project::create([
            'name'                  => 'اختبار إعدادات',
            'code'                  => 'TEST-SCRAP-'.now()->format('His'),
            'default_scrap_percent' => CrownSettings::defaultScrapPercent(),
            'units_multiplier'      => CrownSettings::defaultUnitsMultiplier(),
            'is_active'             => true,
            'status'                => Project::STATUS_DRAFT,
        ]);
        $okNewProject = abs((float) $newProject->default_scrap_percent - 5.5) < 0.01;
        $this->line(sprintf('مشروع جديد يحمل الهالك → %s', $okNewProject ? 'OK' : 'FAIL'));

        CrownSettings::set('default_scrap_percent', 1);

        $steel = RawMaterial::where('code', 'steel')->firstOrFail();
        $rawWh = $stock->getRawWarehouse();
        $balanceBefore = $stock->getBalance($rawWh, $steel)->qty_on_hand;

        $supplier = Supplier::firstOrCreate(
            ['name' => 'مورد اختبار'],
            ['is_active' => true]
        );

        $po = $purchaseOrders->create([
            'supplier_id' => $supplier->id,
            'po_number'   => 'PO-TEST-'.now()->format('His'),
            'items'       => [
                ['raw_material_id' => $steel->id, 'qty_ordered' => 500],
            ],
        ]);
        $purchaseOrders->markSent($po);
        $line = $po->items()->first();
        $purchaseOrders->receiveItem($line, 500);

        $balanceAfter = $stock->getBalance($rawWh, $steel)->fresh()->qty_on_hand;
        $okPo = abs($balanceAfter - ($balanceBefore + 500)) < 0.01;
        $this->line(sprintf('استلام 500 كجم حديد: قبل %s بعد %s → %s', $balanceBefore, $balanceAfter, $okPo ? 'OK' : 'FAIL'));

        if ($okPo && $balanceAfter > $balanceBefore) {
            $stock->getBalance($rawWh, $steel)->update(['qty_on_hand' => $balanceBefore]);
        }

        $summary = $dashboard->projectSummary();
        $shortages = $dashboard->globalShortages();
        $pending = $dashboard->pendingCounts();
        $okDash = $summary['total'] > 0 && is_array($shortages['top_items']) && is_int($pending['material_requests']);
        $this->line(sprintf('لوحة المؤشرات: مشاريع=%d نواقص-أسطر=%d → %s', $summary['total'], $shortages['total_shortage_lines'], $okDash ? 'OK' : 'FAIL'));

        $allOk = $okH && $okScrap && $okNewProject && $okPo && $okDash;

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
