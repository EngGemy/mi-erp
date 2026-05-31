<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'code', 'description',
        'default_scrap_percent', 'units_multiplier', 'is_active',
        'progress_cached', 'status',
    ];

    protected $casts = [
        'default_scrap_percent' => 'float',
        'units_multiplier'      => 'float',
        'is_active'             => 'boolean',
        'progress_cached'       => 'float',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DELIVERED = 'delivered';

    public function variables(): HasMany
    {
        return $this->hasMany(ProjectVariable::class)->orderBy('sort');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class)->orderBy('sort');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->orderBy('sort');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
