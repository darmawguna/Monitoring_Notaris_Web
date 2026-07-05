<?php

namespace App\Models;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Models\Concerns\HasProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Berkas extends Model
{
    use HasFactory, HasProgress;

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
        'pbb_nop',
        'pbb_validasi',
        'pbb_akta_bpjb',
        'status_overall',
        'current_stage_key',
        'created_by',
    ];

    protected $casts = [
        'nilai_transaksi' => 'decimal:2',
        'status_overall' => BerkasStatus::class,
        'current_stage_key' => StageKey::class,
        'penjual_data' => 'array',
        'pembeli_data' => 'array',
        'pihak_persetujuan_data' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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