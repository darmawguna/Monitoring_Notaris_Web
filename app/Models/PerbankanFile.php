<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerbankanFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'perbankan_id',
        'type',
        'path',
    ];


    /**
     * Get the berkas that owns the file.
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Perbankan::class);
    }
}
