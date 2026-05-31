<?php

namespace App\Services;

use App\Models\FinishedReceipt;
use App\Models\FinishedReceiptItem;
use App\Models\Item;
use App\Models\ProjectItemProgress;
use App\Models\Stage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;

class FinishedReceiptService
{
    public function __construct(
        protected StockService $stock,
        protected ProgressService $progress,
        protected InventoryNotifier $notifier,
    ) {}

    public function createFromProduction(WorkOrderItem $woItem, float $qty, ?User $requester = null): FinishedReceipt
    {
        $woItem->load('workOrder.project', 'catalogItem');

        return DB::transaction(function () use ($woItem, $qty, $requester) {
            $receipt = FinishedReceipt::create([
                'work_order_id' => $woItem->work_order_id,
                'status'        => FinishedReceipt::STATUS_PENDING,
                'requested_by'  => $requester?->id,
                'note'          => sprintf(
                    'استلام %s — %s',
                    $woItem->catalogItem->name,
                    number_format($qty, 0)
                ),
            ]);

            FinishedReceiptItem::create([
                'finished_receipt_id' => $receipt->id,
                'work_order_item_id'  => $woItem->id,
                'catalog_item_id'     => $woItem->catalog_item_id,
                'qty'                 => $qty,
            ]);

            $this->notifier->notifyRole(
                'warehouse_manager',
                'طلب استلام منتج تام',
                sprintf('%s × %s — إذن %s', $woItem->catalogItem->name, number_format($qty, 0), $woItem->workOrder->order_number),
                url('/admin/finished-receipts/'.$receipt->id),
                $receipt
            );

            return $receipt->load('items.catalogItem', 'workOrder.project');
        });
    }

    public function approve(FinishedReceipt $receipt, User $approver): FinishedReceipt
    {
        $receipt->update([
            'status'           => FinishedReceipt::STATUS_APPROVED,
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        $receipt->load('workOrder.creator');

        $this->notifier->notifyUser(
            $receipt->workOrder->creator,
            'تمت الموافقة على استلام التام',
            'يمكن لمدير المخازن تأكيد الإدخال للمخزن',
            url('/admin/finished-receipts/'.$receipt->id),
            $receipt
        );

        return $receipt->fresh();
    }

    public function cancel(FinishedReceipt $receipt, User $user): FinishedReceipt
    {
        if (! in_array($receipt->status, [
            FinishedReceipt::STATUS_PENDING,
            FinishedReceipt::STATUS_APPROVED,
        ], true)) {
            throw new \RuntimeException('لا يمكن إلغاء هذا الطلب.');
        }

        return $this->reject($receipt, $user, 'ملغى');
    }

    public function reject(FinishedReceipt $receipt, User $approver, string $reason): FinishedReceipt
    {
        $receipt->update([
            'status'           => FinishedReceipt::STATUS_REJECTED,
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $receipt->load('workOrder.creator');

        $this->notifier->notifyUser(
            $receipt->workOrder->creator,
            'تم رفض استلام التام',
            $reason,
            url('/admin/finished-receipts/'.$receipt->id),
            $receipt
        );

        return $receipt->fresh();
    }

    public function receive(FinishedReceipt $receipt, User $receiver): FinishedReceipt
    {
        if ($receipt->status !== FinishedReceipt::STATUS_APPROVED) {
            throw new \RuntimeException('يجب الموافقة قبل الإدخال للمخزن.');
        }

        $receipt->load(['items.catalogItem', 'items.workOrderItem', 'workOrder.project']);

        $finishedWarehouse = $this->stock->getFinishedWarehouse();
        $manufacturingStage = Stage::query()->where('name', 'تصنيع')->first();

        DB::transaction(function () use ($receipt, $receiver, $finishedWarehouse, $manufacturingStage) {
            foreach ($receipt->items as $line) {
                $this->stock->receiveIn(
                    $finishedWarehouse,
                    $line->catalogItem,
                    (float) $line->qty,
                    $receipt,
                    $receiver
                );

                $this->syncWbsManufacturing(
                    $receipt->workOrder->project,
                    (int) $line->catalog_item_id,
                    (float) $line->qty,
                    $manufacturingStage?->id
                );
            }

            $receipt->update([
                'status'      => FinishedReceipt::STATUS_RECEIVED,
                'received_at' => now(),
            ]);
        });

        $this->progress->rollup($receipt->workOrder->project->fresh());

        $receipt->load('workOrder.creator');

        $this->notifier->notifyUser(
            $receipt->workOrder->creator,
            'تم إدخال المنتج التام للمخزن',
            'تم تحديث WBS للمشروع',
            url('/admin/work-orders/'.$receipt->work_order_id.'/edit'),
            $receipt
        );

        return $receipt->fresh();
    }

    protected function syncWbsManufacturing($project, int $catalogItemId, float $qtyReceived, ?int $stageId): void
    {
        if (! $stageId) {
            return;
        }

        $item = Item::query()
            ->where('project_id', $project->id)
            ->where('catalog_item_id', $catalogItemId)
            ->where('is_active', true)
            ->first();

        if (! $item) {
            return;
        }

        $progress = ProjectItemProgress::firstOrCreate(
            ['item_id' => $item->id, 'stage_id' => $stageId],
            ['done_qty' => 0]
        );

        $progress->increment('done_qty', $qtyReceived);
    }
}
