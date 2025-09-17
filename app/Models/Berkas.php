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

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nomor_berkas',
        'nama_berkas',
        'nama_pemohon',
        'penjual_data',
        'pembeli_data',
        'pihak_persetujuan_data',
        'sertifikat_nomor',
        'sertifikat_luas',
        'sertifikat_jenis',
        'sertifikat_tipe',
        'nilai_transaksi',
        'pbb_sppt',
        'pbb_nop',
        'pbb_validasi',
        'pbb_akta_bpjb',
        'pbb_nomor',
        'bank_kredit',
        'total_cost',
        'total_paid',
        'status_overall',
        'current_stage_key',
        'current_assignee_id',
        'deadline_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_cost' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'nilai_transaksi' => 'decimal:2',
        'deadline_at' => 'datetime',
        'status_overall' => BerkasStatus::class,
        'current_stage_key' => StageKey::class,
        // Beritahu Laravel bahwa kolom-kolom ini adalah JSON
        'penjual_data' => 'array',
        'pembeli_data' => 'array',
        'pihak_persetujuan_data' => 'array',
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