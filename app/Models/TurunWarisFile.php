<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurunWarisFile extends Model
{
    use HasFactory;

    protected $table = 'turun_waris_files';

    protected $fillable = [
        'turun_waris_id',
        'type',
        'path',
    ];

    public function turunWaris(): BelongsTo
    {
        return $this->belongsTo(TurunWaris::class);
    }
}