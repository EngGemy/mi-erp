<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectItemProgress extends Model
{
    protected $table = 'project_item_progress';

    protected $fillable = ['item_id', 'stage_id', 'done_qty'];

    protected $casts = [
        'done_qty' => 'float',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
