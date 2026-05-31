<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $fillable = ['name', 'sort', 'weight'];

    protected $casts = [
        'weight' => 'float',
    ];

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ProjectItemProgress::class);
    }
}
