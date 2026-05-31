<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    public const TYPE_RAW = 'raw';

    public const TYPE_FINISHED = 'finished';

    protected $fillable = ['name', 'type', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
