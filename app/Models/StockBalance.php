<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockBalance extends Model
{
    protected $fillable = ['warehouse_id', 'stockable_type', 'stockable_id', 'qty_on_hand', 'qty_reserved'];

    protected $casts = [
        'qty_on_hand'   => 'float',
        'qty_reserved'  => 'float',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function availableQty(): float
    {
        return max(0, (float) $this->qty_on_hand - (float) $this->qty_reserved);
    }
}
