<?php

namespace App\Http\Controllers;

use App\Models\AppFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function download(AppFile $appFile): StreamedResponse
    {
        // Validasi path
        if (empty($appFile->path)) {
            abort(404, 'Path file tidak tersedia.');
        }

        // Normalisasi path: hapus leading slash jika ada
        $normalizedPath = ltrim($appFile->path, '/');

        // Pastikan file ada di disk 'public'
        if (!Storage::disk('public')->exists($normalizedPath)) {
            \Log::warning('File tidak ditemukan di storage', [
                'app_file_id' => $appFile->id,
                'stored_path' => $appFile->path,
                'normalized_path' => $normalizedPath,
            ]);
            abort(404, 'File tidak ditemukan di server.');
        }

        // Ambil nama file asli, fallback ke UUID jika gagal
        $originalFilename = basename($normalizedPath);
        if (empty($originalFilename) || $originalFilename === '.') {
            $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION);
            $originalFilename = 'dokumen_' . Str::random(8) . ($extension ? '.' . $extension : '');
        }

        return Storage::disk('public')->download($normalizedPath, $originalFilename);
    }
}