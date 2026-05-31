<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogItem extends Model
{
    protected $fillable = [
        'catalog_section_id',
        'code',
        'name',
        'piece_length',
        'unit',
        'formula',
        'scrap_mode',
        'scrap_percent',
        'scrap_fixed',
        'rounding',
        'is_active',
        'sort',
        'weight',
    ];

    protected $casts = [
        'piece_length'  => 'float',
        'scrap_percent' => 'float',
        'scrap_fixed'   => 'float',
        'weight'        => 'float',
        'is_active'     => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CatalogSection::class, 'catalog_section_id');
    }

    public function projectItems(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ItemRecipe::class);
    }

    public function stockBalances(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(StockBalance::class, 'stockable');
    }
}
