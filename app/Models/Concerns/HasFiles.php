<?php
namespace App\Models\Concerns;
use App\Models\AppFile;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFiles
{
    public function files(): MorphMany
    {
        return $this->morphMany(AppFile::class, 'fileable');
    }
}