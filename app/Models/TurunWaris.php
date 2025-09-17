<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurunWaris extends Model
{
    use HasFactory;

    protected $table = 'turun_waris';

    protected $fillable = [
        'nama_kasus',
        'created_by',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(TurunWarisFile::class);
    }
}