<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogSection extends Model
{
    protected $fillable = ['name', 'sort', 'weight_default'];

    protected $casts = [
        'weight_default' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class)->orderBy('sort');
    }
}
