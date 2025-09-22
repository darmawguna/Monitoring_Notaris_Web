<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perbankan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipe_pemohon',
        'nik',
        'nama_debitur',
        'alamat_debitur',
        'ttl_tempat',
        'ttl_tanggal',
        'npwp',
        'email',
        'nomor_pk', // <-- Tambahkan ini
        'nama_kreditur',
        'telepon',
        'berkas_bank',
        'jangka_waktu',
        'tanggal_covernote',
    ];

    protected $casts = [
        'ttl_tanggal' => 'date',
        'tanggal_covernote' => 'date',
    ];
}