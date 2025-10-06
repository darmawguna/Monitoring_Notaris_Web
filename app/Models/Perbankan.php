<?php

namespace App\Models;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany; // <-- Tambahkan ini

class Perbankan extends Model
{
    use HasFactory, HasProgress; // Trait HasFiles tidak diperlukan di sini

    protected $fillable = [
        'tipe_pemohon',
        'nik',
        'nama_debitur',
        'alamat_debitur',
        'ttl_tempat',
        'ttl_tanggal',
        'npwp',
        'email',
        'nomor_pk',
        'nama_kreditur',
        'telepon',
        'jangka_waktu',
        'tanggal_covernote',
        'status_overall',
        'current_stage_key',
        'created_by',
    ];

    protected $casts = [
        'ttl_tanggal' => 'date',
        'tanggal_covernote' => 'date',
        'status_overall' => BerkasStatus::class,
        'current_stage_key' => StageKey::class,
    ];

    /**
     * Mendapatkan satu file yang terhubung dengan record Perbankan ini.
     */
    public function files(): HasMany
    {
        return $this->hasMany(PerbankanFile::class);
    }
    
}