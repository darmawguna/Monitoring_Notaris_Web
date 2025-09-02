<?php

namespace App\Models;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Berkas extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor',
        'nama_berkas',
        'penjual',
        'pembeli',
        'sertifikat_nama',
        'persetujuan',
        'total_cost',
        'total_paid',
        'status_overall',
        'current_stage_key',
        'current_assignee_id',
        'deadline_at',
        'created_by',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'deadline_at' => 'datetime',
        'status_overall' => BerkasStatus::class,
        'current_stage_key' => StageKey::class,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(BerkasFile::class);
    }
}