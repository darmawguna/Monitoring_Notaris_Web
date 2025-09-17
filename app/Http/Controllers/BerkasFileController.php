<?php

namespace App\Http\Controllers;

use App\Models\BerkasFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BerkasFileController extends Controller
{
    /**
     * Menangani permintaan untuk mengunduh file lampiran berkas.
     */
    public function download(BerkasFile $berkasFile): StreamedResponse
    {
        $disk = Storage::disk('public');
        // Pastikan file benar-benar ada di storage untuk menghindari error.
        if (!Storage::disk('public')->exists($berkasFile->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Gunakan metode download dari Laravel Storage.
        // Ini akan secara otomatis mengatur semua header HTTP yang diperlukan.
        return $disk->download($berkasFile->path);
    }
}