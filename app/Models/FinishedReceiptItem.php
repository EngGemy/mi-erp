<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishedReceiptItem extends Model
{
    protected $fillable = [
        'finished_receipt_id', 'work_order_item_id', 'catalog_item_id', 'qty',
    ];

    protected $casts = ['qty' => 'float'];

    public function finishedReceipt(): BelongsTo
    {
        return $this->belongsTo(FinishedReceipt::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
