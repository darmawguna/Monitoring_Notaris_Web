<?php

namespace App\Models\Concerns;

use App\Models\Progress;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasProgress
{
    public function progress(): MorphMany
    {
        return $this->morphMany(Progress::class, 'progressable');
    }
}