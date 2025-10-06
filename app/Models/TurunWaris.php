<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasProgress;
use App\Models\Concerns\HasFiles;
use App\Enums\BerkasStatus;
use App\Enums\StageKey;

class TurunWaris extends Model
{
    use HasFactory, HasProgress;

    protected $table = 'turun_waris';

    protected $fillable = [
        'nama_kasus',
        'created_by',
        'status_overall',
        'current_stage_key',
    ];
    protected $casts = [
        'status_overall' => BerkasStatus::class,
        'current_stage_key' => StageKey::class,
    ];

    public function files(): HasMany
    {
        return $this->hasMany(TurunWarisFile::class);
    }
}