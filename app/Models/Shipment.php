<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'project_id', 'name', 'shipped_at', 'sort',
        'driver_name', 'vehicle_no', 'responsible', 'arrival_time', 'notes',
    ];

    protected $casts = [
        'shipped_at'    => 'date',
        'arrival_time'  => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
