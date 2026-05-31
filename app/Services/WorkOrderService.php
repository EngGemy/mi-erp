<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(
        protected MaterialRequestService $materialRequests,
        protected FinishedReceiptService $finishedReceipts,
    ) {}

    public function issue(WorkOrder $workOrder, ?User $user = null): WorkOrder
    {
        if ($workOrder->materialRequest()->exists()) {
            throw new \RuntimeException('تم إصدار طلب صرف مسبقاً لهذا الإذن.');
        }

        $workOrder->load('items.catalogItem.recipes');

        foreach ($workOrder->items as $item) {
            if ($item->catalogItem->recipes->isEmpty()) {
                throw new \RuntimeException(
                    'الصنف '.$item->catalogItem->code.' بلا وصفة خام — عرّف item_recipes أولاً.'
                );
            }
        }

        return DB::transaction(function () use ($workOrder, $user) {
            $workOrder->update([
                'status'    => WorkOrder::STATUS_ISSUED,
                'issued_at' => now(),
            ]);

            $this->materialRequests->createFromWorkOrder($workOrder, $user);

            return $workOrder->fresh(['materialRequest.items.rawMaterial', 'items.catalogItem']);
        });
    }

    /**
     * @return array{receipt: \App\Models\FinishedReceipt, work_order_item: WorkOrderItem}
     */
    public function registerProduction(WorkOrderItem $woItem, float $qtyProduced, ?User $user = null): array
    {
        if ($qtyProduced <= 0) {
            throw new \InvalidArgumentException('الكمية المنتجة يجب أن تكون أكبر من صفر.');
        }

        $woItem->load('workOrder.materialRequest');

        $mr = $woItem->workOrder->materialRequest;

        if (! $mr || $mr->status !== MaterialRequest::STATUS_ISSUED) {
            throw new \RuntimeException('يجب صرف الخامات أولاً قبل تسجيل الإنتاج.');
        }

        return DB::transaction(function () use ($woItem, $qtyProduced, $user) {
            $woItem->update(['qty_produced' => (float) $woItem->qty_produced + $qtyProduced]);

            $receipt = $this->finishedReceipts->createFromProduction($woItem, $qtyProduced, $user);

            return ['receipt' => $receipt, 'work_order_item' => $woItem->fresh()];
        });
    }
}
