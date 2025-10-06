<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\TurunWaris;
use Illuminate\Http\Request;

class TurunWarisFileController extends Controller
{
    public function download(TurunWaris $turunWaris): StreamedResponse
    {
        $disk = Storage::disk('public');
        // Pastikan file benar-benar ada di storage untuk menghindari error.
        if (!Storage::disk('public')->exists($turunWaris->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Gunakan metode download dari Laravel Storage.
        // Ini akan secara otomatis mengatur semua header HTTP yang diperlukan.
        return $disk->download($turunWaris->path);
    }
}
