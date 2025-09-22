<?php

namespace App\Models;

use App\Enums\PembayaranStatus; // Impor Enum
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'berkas_id',
        'receipt_number',
        'nama_pemohon_kwitansi', // <-- Tambahkan ini
        'amount',
        'status_pembayaran',
        'detail_biaya',
        'notes_kwitansi', // <-- Tambahkan ini
        'payment_method',
        'issued_at',
        'informasi_kwitansi', // 'notes' lama bisa dihapus jika 'notes_kwitansi' adalah penggantinya
        'issued_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'detail_biaya' => 'array',
        'issued_at' => 'date',
        'status_pembayaran' => PembayaranStatus::class, // Cast ke Enum
    ];

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }
    // ... relasi lain
}