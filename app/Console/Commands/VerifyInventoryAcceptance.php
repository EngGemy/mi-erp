<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\RawMaterial;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Services\BomEngine;
use App\Services\MaterialRequestService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Console\Command;

class VerifyInventoryAcceptance extends Command
{
    protected $signature = 'crown:verify-inventory';

    protected $description = 'اختبار قبول المخزون ودورة التصنيع';

    public function handle(
        BomEngine $engine,
        WorkOrderService $workOrders,
        MaterialRequestService $materialRequests,
        StockService $stock,
    ): int {
        $this->call('db:seed', ['--class' => 'InventorySeeder', '--force' => true]);

        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        if (! $project) {
            $this->error('مشروع CROWN-FATTEN غير موجود');

            return self::FAILURE;
        }

        $h = (float) (collect($engine->calculate($project->fresh('variables')))->firstWhere('code', 'leg_post')['total'] ?? 0);
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s → %s', $h, $okH ? 'OK' : 'FAIL'));

        $steel = RawMaterial::where('code', 'steel')->firstOrFail();
        $legPost = CatalogItem::where('code', 'leg_post')->firstOrFail();
        $rawWh = $stock->getRawWarehouse();

        $balance = StockBalance::where('warehouse_id', $rawWh->id)
            ->where('stockable_type', RawMaterial::class)
            ->where('stockable_id', $steel->id)
            ->first();

        $balance->update(['qty_on_hand' => 500]);

        $productionUser = User::where('email', 'production@crown-bom.test')->firstOrFail();
        $warehouseUser = User::where('email', 'warehouse@crown-bom.test')->firstOrFail();

        $wo = WorkOrder::create([
            'project_id'   => $project->id,
            'order_number' => 'WO-TEST-'.now()->format('His'),
            'status'       => WorkOrder::STATUS_DRAFT,
            'created_by'   => $productionUser->id,
        ]);
        $wo->items()->create([
            'catalog_item_id' => $legPost->id,
            'qty_ordered'     => 100,
        ]);

        $workOrders->issue($wo, $productionUser);
        $mr = $wo->fresh()->materialRequest;
        $okMr = $mr && $mr->status === MaterialRequest::STATUS_PENDING;
        $steelLine = $mr->items->firstWhere('raw_material_id', $steel->id);
        $okQty = $steelLine && abs($steelLine->qty_requested - 200) < 0.01;
        $this->line(sprintf('طلب صرف 200 كجم حديد → %s', ($okMr && $okQty) ? 'OK' : 'FAIL'));

        $materialRequests->approve($mr, $warehouseUser);
        $issueResult = $materialRequests->issue($mr->fresh(), $warehouseUser);
        $okIssue = $issueResult['ok'] ?? false;
        $balance->refresh();
        $okStock = abs($balance->qty_on_hand - 300) < 0.01;
        $this->line(sprintf('بعد الصرف رصيد الحديد = %s (متوقع 300) → %s', $balance->qty_on_hand, ($okIssue && $okStock) ? 'OK' : 'FAIL'));

        $wo2 = WorkOrder::create([
            'project_id'   => $project->id,
            'order_number' => 'WO-BLOCK-'.now()->format('His'),
            'status'       => WorkOrder::STATUS_DRAFT,
            'created_by'   => $productionUser->id,
        ]);
        $wo2->items()->create(['catalog_item_id' => $legPost->id, 'qty_ordered' => 100]);
        $balance->update(['qty_on_hand' => 50]);
        $workOrders->issue($wo2, $productionUser);
        $mr2 = $wo2->fresh()->materialRequest;
        $materialRequests->approve($mr2, $warehouseUser);
        $failIssue = $materialRequests->issue($mr2, $warehouseUser);
        $okBlock = ! ($failIssue['ok'] ?? true);
        $this->line(sprintf('منع الصرف برصيد غير كافٍ → %s', $okBlock ? 'OK' : 'FAIL'));

        $woItem = $wo->items()->first();
        $receiptResult = $workOrders->registerProduction($woItem, 100, $productionUser);
        $receipt = $receiptResult['receipt'];

        app(\App\Services\FinishedReceiptService::class)->approve($receipt, $warehouseUser);
        app(\App\Services\FinishedReceiptService::class)->receive($receipt->fresh(), $warehouseUser);

        $finishedWh = Warehouse::where('type', Warehouse::TYPE_FINISHED)->first();
        $finBalance = StockBalance::where('warehouse_id', $finishedWh->id)
            ->where('stockable_type', CatalogItem::class)
            ->where('stockable_id', $legPost->id)
            ->first();

        $okFin = $finBalance && abs($finBalance->qty_on_hand - 100) < 0.01;
        $this->line(sprintf('رصيد التام leg_post = %s → %s', $finBalance?->qty_on_hand ?? 0, $okFin ? 'OK' : 'FAIL'));

        $project->refresh();
        $okProgress = ($project->progress_cached ?? 0) > 0;
        $this->line(sprintf('WBS progress_cached = %s%% → %s', $project->progress_cached, $okProgress ? 'OK' : 'FAIL'));

        $allOk = $okH && $okMr && $okQty && $okIssue && $okStock && $okBlock && $okFin && $okProgress;
        $this->info($allOk ? 'اختبار المخزون نجح.' : 'فشل اختبار.');

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
