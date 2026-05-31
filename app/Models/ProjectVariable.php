<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVariable extends Model
{
    protected $fillable = ['project_id', 'key', 'label', 'value', 'unit', 'sort'];

    protected $casts = ['value' => 'float'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
