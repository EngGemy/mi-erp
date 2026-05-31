<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        protected StockService $stock,
        protected InventoryNotifier $notifier,
    ) {}

    public function generatePoNumber(): string
    {
        return 'PO-'.now()->format('ymd-His');
    }

    /**
     * @return array<int, array{raw_material_id: int, code: string, name: string, unit: string, shortage: float, available: float, required: float}>
     */
    public function rawMaterialShortages(): array
    {
        $warehouse = $this->stock->getRawWarehouse();
        $requiredByMaterial = [];

        MaterialRequestItem::query()
            ->whereHas('materialRequest', fn ($q) => $q->whereIn('status', [
                MaterialRequest::STATUS_PENDING,
                MaterialRequest::STATUS_APPROVED,
            ]))
            ->with('rawMaterial')
            ->get()
            ->each(function (MaterialRequestItem $line) use (&$requiredByMaterial) {
                $remaining = max(0, (float) $line->qty_requested - (float) $line->qty_issued);
                if ($remaining > 0) {
                    $requiredByMaterial[$line->raw_material_id] = ($requiredByMaterial[$line->raw_material_id] ?? 0) + $remaining;
                }
            });

        $shortages = [];

        foreach (RawMaterial::where('is_active', true)->orderBy('name')->get() as $material) {
            $required = (float) ($requiredByMaterial[$material->id] ?? 0);
            $available = $this->stock->availableQty($warehouse, $material);

            if ($required > $available + 0.0001) {
                $shortages[] = [
                    'raw_material_id' => $material->id,
                    'code'            => $material->code,
                    'name'            => $material->name,
                    'unit'            => $material->unit,
                    'available'       => round($available, 4),
                    'required'        => round($required, 4),
                    'shortage'        => round($required - $available, 4),
                ];
            }
        }

        usort($shortages, fn ($a, $b) => $b['shortage'] <=> $a['shortage']);

        return $shortages;
    }

    /**
     * @param  array{supplier_id: int, items: array<int, array{raw_material_id: int, qty_ordered: float, unit_price?: float|null}>, notes?: string|null}  $data
     */
    public function create(array $data, ?User $creator = null): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $po = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'po_number'   => $data['po_number'] ?? $this->generatePoNumber(),
                'status'      => PurchaseOrder::STATUS_DRAFT,
                'created_by'  => $creator?->id,
                'order_date'  => $data['order_date'] ?? now()->toDateString(),
                'notes'       => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                if (empty($row['raw_material_id']) || ($row['qty_ordered'] ?? 0) <= 0) {
                    continue;
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'raw_material_id'   => $row['raw_material_id'],
                    'qty_ordered'       => $row['qty_ordered'],
                    'unit_price'      => $row['unit_price'] ?? null,
                ]);
            }

            return $po->fresh('items.rawMaterial', 'supplier');
        });
    }

    public function createFromShortages(int $supplierId, ?User $creator = null): PurchaseOrder
    {
        $lines = $this->rawMaterialShortages();

        if ($lines === []) {
            throw new \RuntimeException('لا توجد مواد خام ناقصة حالياً.');
        }

        return $this->create([
            'supplier_id' => $supplierId,
            'notes'       => 'أمر شراء مُنشأ تلقائياً من النواقص',
            'items'       => array_map(fn ($line) => [
                'raw_material_id' => $line['raw_material_id'],
                'qty_ordered'     => $line['shortage'],
            ], $lines),
        ], $creator);
    }

    public function markSent(PurchaseOrder $po, ?User $user = null): PurchaseOrder
    {
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new \RuntimeException('يمكن إرسال المسودة فقط.');
        }

        $po->update(['status' => PurchaseOrder::STATUS_SENT]);

        $this->notifier->notifyRole(
            'warehouse_manager',
            'أمر شراء بانتظار الاستلام',
            sprintf('أمر %s من المورد %s', $po->po_number, $po->supplier?->name),
            url('/admin/purchase-orders/'.$po->id.'/receive'),
            $po
        );

        $this->notifier->notifyRole(
            'admin',
            'أمر شراء بانتظار الاستلام',
            sprintf('أمر %s', $po->po_number),
            url('/admin/purchase-orders/'.$po->id.'/receive'),
            $po
        );

        return $po->fresh();
    }

    public function receiveItem(PurchaseOrderItem $item, float $qty, ?User $user = null): PurchaseOrderItem
    {
        if ($qty <= 0) {
            throw new \RuntimeException('كمية الاستلام يجب أن تكون أكبر من صفر.');
        }

        $remaining = $item->remainingQty();
        if ($qty > $remaining + 0.0001) {
            throw new \RuntimeException('الكمية تتجاوز المتبقي في أمر الشراء.');
        }

        $po = $item->purchaseOrder;

        if (! in_array($po->status, [
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
        ], true)) {
            throw new \RuntimeException('لا يمكن الاستلام إلا لأمر مُرسَل أو مستلم جزئياً.');
        }

        DB::transaction(function () use ($item, $qty, $user, $po) {
            $warehouse = $this->stock->getRawWarehouse();
            $this->stock->receiveIn($warehouse, $item->rawMaterial, $qty, $item, $user);

            $item->increment('qty_received', $qty);
            $this->refreshPoStatus($po->fresh('items'));
        });

        return $item->fresh('rawMaterial', 'purchaseOrder');
    }

    public function refreshPoStatus(PurchaseOrder $po): void
    {
        $po->load('items');

        if ($po->items->isEmpty()) {
            return;
        }

        $allReceived = $po->items->every(fn (PurchaseOrderItem $i) => $i->remainingQty() <= 0.0001);
        $anyReceived = $po->items->contains(fn (PurchaseOrderItem $i) => (float) $i->qty_received > 0);

        if ($allReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
        } elseif ($anyReceived) {
            $po->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (in_array($po->status, [PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_CANCELLED], true)) {
            throw new \RuntimeException('لا يمكن إلغاء هذا الأمر.');
        }

        $po->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return $po->fresh();
    }
}
