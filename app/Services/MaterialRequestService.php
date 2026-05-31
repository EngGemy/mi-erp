<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class MaterialRequestService
{
    public function __construct(
        protected StockService $stock,
        protected InventoryNotifier $notifier,
    ) {}

    public function createFromWorkOrder(WorkOrder $workOrder, ?User $requester = null): MaterialRequest
    {
        $workOrder->load(['items.catalogItem.recipes.rawMaterial', 'project']);

        $aggregated = [];

        foreach ($workOrder->items as $woItem) {
            foreach ($woItem->catalogItem->recipes as $recipe) {
                $rawId = $recipe->raw_material_id;
                $qty = (float) $recipe->qty_per_unit * (float) $woItem->qty_ordered;
                $aggregated[$rawId] = ($aggregated[$rawId] ?? 0) + $qty;
            }
        }

        return DB::transaction(function () use ($workOrder, $requester, $aggregated) {
            $request = MaterialRequest::create([
                'work_order_id' => $workOrder->id,
                'status'        => MaterialRequest::STATUS_PENDING,
                'requested_by'  => $requester?->id ?? $workOrder->created_by,
                'note'          => 'طلب صرف خام لإذن إنتاج '.$workOrder->order_number,
            ]);

            foreach ($aggregated as $rawMaterialId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                MaterialRequestItem::create([
                    'material_request_id' => $request->id,
                    'raw_material_id'     => $rawMaterialId,
                    'qty_requested'       => round($qty, 4),
                ]);
            }

            $request->load('items.rawMaterial', 'workOrder.project');

            $this->notifier->notifyRole(
                'warehouse_manager',
                'طلب صرف خام جديد',
                sprintf('إذن إنتاج %s — مشروع %s', $workOrder->order_number, $workOrder->project->name),
                url('/admin/material-requests/'.$request->id),
                $request
            );

            return $request;
        });
    }

    public function approve(MaterialRequest $request, User $approver): MaterialRequest
    {
        $request->update([
            'status'      => MaterialRequest::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $request->load('workOrder.creator', 'workOrder.project');

        $this->notifier->notifyUser(
            $request->workOrder->creator,
            'تمت الموافقة على طلب الصرف',
            sprintf('إذن %s — يمكنك متابعة التصنيع بعد الصرف', $request->workOrder->order_number),
            url('/admin/work-orders/'.$request->work_order_id.'/edit'),
            $request
        );

        return $request->fresh();
    }

    public function cancel(MaterialRequest $request, User $user): MaterialRequest
    {
        if (! in_array($request->status, [
            MaterialRequest::STATUS_PENDING,
            MaterialRequest::STATUS_APPROVED,
        ], true)) {
            throw new \RuntimeException('لا يمكن إلغاء هذا الطلب.');
        }

        return $this->reject($request, $user, 'ملغى بواسطة '.($user->name ?? 'المستخدم'));
    }

    public function reject(MaterialRequest $request, User $approver, string $reason): MaterialRequest
    {
        $request->update([
            'status'           => MaterialRequest::STATUS_REJECTED,
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        $request->load('workOrder.creator');

        $this->notifier->notifyUser(
            $request->workOrder->creator,
            'تم رفض طلب الصرف',
            $reason,
            url('/admin/material-requests/'.$request->id.'/edit'),
            $request
        );

        return $request->fresh();
    }

    /**
     * @return array{ok: bool, message?: string, shortages?: array}
     */
    public function issue(MaterialRequest $request, User $issuer): array
    {
        if ($request->status !== MaterialRequest::STATUS_APPROVED) {
            return ['ok' => false, 'message' => 'يجب الموافقة على الطلب قبل الصرف.'];
        }

        $request->load('items.rawMaterial');

        $warehouse = $this->stock->getRawWarehouse();
        $lines = $request->items->map(fn ($item) => [
            'material' => $item->rawMaterial,
            'qty'      => (float) $item->qty_requested,
        ])->all();

        $check = $this->stock->checkAvailability($warehouse, $lines);

        if (! $check['ok']) {
            return ['ok' => false, 'message' => 'رصيد غير كافٍ', 'shortages' => $check['shortages']];
        }

        DB::transaction(function () use ($request, $issuer, $warehouse) {
            foreach ($request->items as $item) {
                $qty = (float) $item->qty_requested;
                $this->stock->issueOut($warehouse, $item->rawMaterial, $qty, $request, $issuer);
                $item->update(['qty_issued' => $qty]);
            }

            $request->update([
                'status'    => MaterialRequest::STATUS_ISSUED,
                'issued_at' => now(),
            ]);

            $request->workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        });

        $request->load('workOrder.creator', 'workOrder.project');

        $this->notifier->notifyUser(
            $request->workOrder->creator,
            'تم صرف الخامات',
            sprintf('إذن %s — تم خصم الخام من المخزن', $request->workOrder->order_number),
            url('/admin/work-orders/'.$request->work_order_id.'/edit'),
            $request
        );

        return ['ok' => true];
    }
}
