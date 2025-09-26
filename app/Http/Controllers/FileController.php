<?php

namespace App\Http\Controllers;

use App\Models\AppFile; // Gunakan model baru
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function download(AppFile $appFile): StreamedResponse
    {
        // Pastikan file benar-benar ada di storage
        if (!Storage::disk('public')->exists($appFile->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Gunakan metode download dari Laravel Storage.
        return Storage::disk('public')->download($appFile->path);
    }
}


?>