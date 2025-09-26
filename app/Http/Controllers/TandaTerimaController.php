<?php

namespace App\Http\Controllers;

use App\Models\TandaTerimaSertifikat;
use Carbon\Carbon; // <-- 1. Tambahkan use statement untuk Carbon
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class TandaTerimaController extends Controller
{
    public function downloadGambar(TandaTerimaSertifikat $record): StreamedResponse
    {
        $filePath = $record->dokumen_akhir_path;

        // Pastikan path file ada dan file-nya benar-benar ada di storage.
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        // Gunakan metode download dari Laravel Storage.
        return Storage::disk('public')->download($filePath);
    }
    public function download(TandaTerimaSertifikat $record)
    {
        $templatePath = storage_path('app/template/template_serah_terima.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template dokumen tidak ditemukan.');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // 2. Siapkan tanggal dengan cara yang lebih aman
        $tanggalSerahTerima = $record->tanggal_menyerahkan
            ? $record->tanggal_menyerahkan->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        $data = [
            'penyerah_1' => $record->penyerah ?? 'N/A',
            'penyerah_2' => $record->penyerah ?? 'N/A',
            'tanggal' => $tanggalSerahTerima,
            // Ganti placeholder ${informasi_tambahan} dengan konten dari RichEditor
            'informasi_tambahan' => $record->informasi_tambahan ?? '-',
        ];

        // Ganti semua placeholder sederhana
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // 3. Logika untuk RichEditor jika Anda memutuskan untuk menggunakannya di masa depan
        // Saat ini, kita menggunakan sertifikat_info yang sederhana
        /*
        $htmlContent = $record->informasi_tambahan ?? '<p>-</p>';
        $escapedHtml = htmlspecialchars($htmlContent, ENT_QUOTES, 'UTF-8');
        $templateProcessor->setValue('informasi_tambahan', $escapedHtml);
        */

        $fileName = 'Tanda Terima - ' . ($record->penerima ?? 'Tanpa Nama') . '.docx';
        $tempDirectory = storage_path('app/temp/');

        if (!File::isDirectory($tempDirectory)) {
            File::makeDirectory($tempDirectory, 0755, true, true);
        }

        $tempFile = $tempDirectory . $fileName;

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}

