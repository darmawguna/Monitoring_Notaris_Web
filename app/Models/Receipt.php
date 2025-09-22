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
        'amount',
        'status_pembayaran', // Tambahkan ini
        'payment_method',
        'detail_biaya', // Tambahkan ini
        'issued_at',
        'notes',
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