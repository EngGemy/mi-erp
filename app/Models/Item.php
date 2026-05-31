<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'project_id', 'section_id', 'catalog_item_id', 'code', 'name', 'piece_length', 'unit',
        'formula', 'scrap_mode', 'scrap_percent', 'scrap_fixed', 'scrap_formula',
        'rounding', 'required_override', 'weight', 'sort', 'is_active', 'notes',
    ];

    protected $casts = [
        'piece_length'      => 'float',
        'scrap_percent'     => 'float',
        'scrap_fixed'       => 'float',
        'required_override' => 'float',
        'weight'            => 'float',
        'is_active'         => 'boolean',
    ];

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ProjectItemProgress::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
