<?php

namespace App\Models;

use App\Enums\StageKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_key',
        'default_days',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stage_key' => StageKey::class,
        'default_days' => 'integer',
    ];

    /**
     * Get the user who last updated the configuration.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}