<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class StockService
{
    public function getRawWarehouse(): Warehouse
    {
        return Warehouse::query()
            ->where('type', Warehouse::TYPE_RAW)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getFinishedWarehouse(): Warehouse
    {
        return Warehouse::query()
            ->where('type', Warehouse::TYPE_FINISHED)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getBalance(Warehouse $warehouse, Model $stockable): StockBalance
    {
        return StockBalance::firstOrCreate(
            [
                'warehouse_id'  => $warehouse->id,
                'stockable_type' => $stockable::class,
                'stockable_id'   => $stockable->id,
            ],
            ['qty_on_hand' => 0, 'qty_reserved' => 0]
        );
    }

    public function availableQty(Warehouse $warehouse, Model $stockable): float
    {
        return $this->getBalance($warehouse, $stockable)->availableQty();
    }

    /**
     * @return array{ok: bool, shortages: array<int, array{code: string, name: string, required: float, available: float}>}
     */
    public function checkAvailability(Warehouse $warehouse, array $lines): array
    {
        $shortages = [];

        foreach ($lines as $line) {
            /** @var RawMaterial $material */
            $material = $line['material'];
            $required = (float) $line['qty'];
            $available = $this->availableQty($warehouse, $material);

            if ($available + 0.0001 < $required) {
                $shortages[] = [
                    'code'      => $material->code,
                    'name'      => $material->name,
                    'required'  => $required,
                    'available' => $available,
                    'shortage'  => round($required - $available, 4),
                ];
            }
        }

        return ['ok' => $shortages === [], 'shortages' => $shortages];
    }

    public function issueOut(
        Warehouse $warehouse,
        Model $stockable,
        float $qty,
        ?Model $reference = null,
        ?User $user = null
    ): void {
        if ($qty <= 0) {
            return;
        }

        $balance = $this->getBalance($warehouse, $stockable);

        if ($balance->availableQty() + 0.0001 < $qty) {
            throw new \RuntimeException('الرصيد غير كافٍ للصرف.');
        }

        $balance->decrement('qty_on_hand', $qty);

        $this->recordMovement($warehouse, $stockable, StockMovement::TYPE_OUT, $qty, $reference, $user);
    }

    public function receiveIn(
        Warehouse $warehouse,
        Model $stockable,
        float $qty,
        ?Model $reference = null,
        ?User $user = null
    ): void {
        if ($qty <= 0) {
            return;
        }

        $balance = $this->getBalance($warehouse, $stockable);
        $balance->increment('qty_on_hand', $qty);

        $this->recordMovement($warehouse, $stockable, StockMovement::TYPE_IN, $qty, $reference, $user);
    }

    public function recordMovement(
        Warehouse $warehouse,
        Model $stockable,
        string $type,
        float $qty,
        ?Model $reference = null,
        ?User $user = null
    ): StockMovement {
        return StockMovement::create([
            'warehouse_id'   => $warehouse->id,
            'stockable_type' => $stockable::class,
            'stockable_id'   => $stockable->id,
            'type'           => $type,
            'qty'            => $qty,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id'   => $reference?->getKey(),
            'user_id'        => $user?->id,
            'created_at'     => now(),
        ]);
    }
}
