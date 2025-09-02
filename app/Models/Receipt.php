<?php

namespace App\Models;

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
        'payment_method',
        'issued_at',
        'notes',
        'issued_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
    ];

    /**
     * Get the berkas that owns the receipt.
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    /**
     * Get the user who issued the receipt.
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}