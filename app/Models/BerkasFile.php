<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'berkas_id',
        'type',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    /**
     * Get the berkas that owns the file.
     */
    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}