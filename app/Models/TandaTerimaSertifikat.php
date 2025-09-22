<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TandaTerimaSertifikat extends Model
{
    use HasFactory;

    protected $fillable = [
        'penyerah',
        'penerima',
        'tanggal_terima',
        'sertifikat_info',
        'dokumen_akhir_path',
        'created_by',
    ];

    protected $casts = [
        'tanggal_terima' => 'date',
    ];

    /**
     * Menghubungkan ke model User yang membuat record ini.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}