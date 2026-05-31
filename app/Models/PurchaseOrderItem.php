<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'raw_material_id', 'qty_ordered', 'qty_received', 'unit_price',
    ];

    protected $casts = [
        'qty_ordered'  => 'float',
        'qty_received' => 'float',
        'unit_price' => 'float',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function remainingQty(): float
    {
        return max(0, (float) $this->qty_ordered - (float) $this->qty_received);
    }
}
