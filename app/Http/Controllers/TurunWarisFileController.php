<?php

namespace App\Http\Controllers;

use App\Models\TurunWarisFile; // <-- Gunakan model yang benar
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TurunWarisFileController extends Controller
{
    public function download(TurunWarisFile $turunWarisFile): StreamedResponse
    {
        $disk = Storage::disk('public');
        // Pastikan file benar-benar ada di storage untuk menghindari error.
        if (!Storage::disk('public')->exists($turunWarisFile->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return $disk->download($turunWarisFile->path);
    }
}