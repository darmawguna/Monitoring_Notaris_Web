<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progress extends Model
{
    use HasFactory;

    protected $table = 'progress'; // Eksplisit karena nama model singular

    protected $fillable = [
        'berkas_id',
        'stage_key',
        'status',
        'assignee_id',
        'notes',
        'deadline',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}