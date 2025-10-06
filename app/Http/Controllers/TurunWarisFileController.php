<?php

namespace App\Http\Controllers;

use App\Models\TurunWarisFile; // <-- Gunakan model yang benar
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TurunWarisFileController extends Controller
{
    public function download($id): StreamedResponse
    {
        $turunWarisFile = TurunWarisFile::findOrFail($id);
        // Pastikan path file tidak kosong
        if (empty($turunWarisFile->path)) {
            abort(404, 'Data file tidak lengkap.');
        }

        // Pastikan file benar-benar ada di storage
        if (!Storage::disk('public')->exists($turunWarisFile->path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        $originalFilename = basename($turunWarisFile->path);
        return Storage::disk('public')->download($turunWarisFile->path, $originalFilename);
    }
}