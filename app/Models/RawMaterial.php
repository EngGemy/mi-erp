<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RawMaterial extends Model
{
    protected $fillable = ['code', 'name', 'unit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function recipes(): HasMany
    {
        return $this->hasMany(ItemRecipe::class);
    }

    public function stockBalances(): MorphMany
    {
        return $this->morphMany(StockBalance::class, 'stockable');
    }
}
