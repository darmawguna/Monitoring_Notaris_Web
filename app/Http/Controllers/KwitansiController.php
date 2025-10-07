<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;

class KwitansiController extends Controller
{
    public function download(Receipt $receipt)
    {
        try {
            Log::info('--- MEMULAI PROSES DOWNLOAD KWITANSI ---');
            Log::info('Menerima objek Receipt via Route-Model Binding. ID: ' . $receipt->id);

            $templatePath = storage_path('app/template/template_kwitansi.docx');
            Log::info("Mengecek keberadaan template di path: {$templatePath}");

            if (!file_exists($templatePath)) {
                Log::error('GAGAL: File template kwitansi tidak ditemukan di path yang ditentukan.');
                abort(404, 'Template kwitansi tidak ditemukan.');
            }
            Log::info('Berhasil menemukan file template.');

            $doc = new TemplateProcessor($templatePath);
            Log::info('Berhasil memuat template ke dalam PHPWord TemplateProcessor.');

            // ---------- Mengisi data ke dalam template ----------
            $doc->setValue('nama_pemohon', $receipt->nama_pemohon_kwitansi ?? 'N/A');
            $doc->setValue('nomor_kwitansi', $receipt->receipt_number ?? 'N/A');
            $doc->setValue('notes_kwitansi', $receipt->notes_kwitansi ?? '');

            $tanggal = $receipt->issued_at
                ? Carbon::parse($receipt->issued_at)->translatedFormat('d F Y')
                : now()->translatedFormat('d F Y');
            $doc->setValue('tanggal_kwitansi', $tanggal);
            $doc->setValue('info_sertifikat', (string) $receipt->informasi_kwitansi);

            $rawItems = $receipt->detail_biaya ?? [];
            $items = array_map(function ($it) {
                $desc = (string) ($it['deskripsi'] ?? '');
                $amt = (int) preg_replace('/\D/', '', (string) ($it['jumlah'] ?? 0));
                return ['deskripsi' => $desc, 'jumlah' => $amt];
            }, is_array($rawItems) ? $rawItems : $rawItems->toArray());

            $totalItems = array_sum(array_column($items, 'jumlah'));
            $grandTotal = count($items) ? $totalItems : (int) ($receipt->amount ?? 0);

            $doc->setValue('jumlah', number_format($grandTotal, 0, ',', '.'));
            $doc->setValue('terbilang', $this->terbilang($grandTotal) . ' Rupiah');
            Log::info('Berhasil mengisi data umum (nama, tanggal, total).');

            // ---------- Mengisi item rincian (clone row) ----------
            if (count($items)) {
                $doc->cloneRow('deskripsi_item-1', count($items));
                $doc->cloneRow('deskripsi_item-2', count($items));
                foreach ($items as $i => $it) {
                    $n = $i + 1;
                    $doc->setValue("deskripsi_item-1#{$n}", $it['deskripsi']);
                    $doc->setValue("jumlah_item-1#{$n}", number_format($it['jumlah'], 0, ',', '.'));
                    $doc->setValue("deskripsi_item-2#{$n}", $it['deskripsi']);
                    $doc->setValue("jumlah_item-2#{$n}", number_format($it['jumlah'], 0, ',', '.'));
                }
                Log::info('Berhasil mengisi ' . count($items) . ' item rincian.');
            } else {
                $doc->setValue('deskripsi-item-1', '-');
                $doc->setValue('jumlah_item-1', '0');
                $doc->setValue('deskripsi_item-2', '-');
                $doc->setValue('jumlah_item-2', '0');
                Log::info('Tidak ada item rincian, mengisi placeholder default.');
            }

            // ---------- Menyiapkan file untuk disimpan ----------
            $displayName = $receipt->nama_pemohon_kwitansi ?: 'Tanpa Nama';
            $fileName = 'Kwitansi - ' . Str::slug($displayName) . '.docx';
            $tempDir = storage_path('app/temp/');

            Log::info("Mengecek direktori sementara: {$tempDir}");
            if (!File::isDirectory($tempDir)) {
                Log::info('Direktori sementara tidak ada, mencoba membuatnya...');
                File::makeDirectory($tempDir, 0775, true);
                Log::info('Berhasil membuat direktori sementara.');
            }

            $tempPath = $tempDir . $fileName;
            Log::info("Mencoba menyimpan file yang telah diproses ke: {$tempPath}");

            $doc->saveAs($tempPath);
            Log::info('Berhasil menyimpan file sementara ke disk.');

            if (!file_exists($tempPath)) {
                Log::error('FATAL: File sementara tidak ada setelah proses saveAs(). Periksa izin tulis folder storage/app/temp/.');
                abort(500, 'Gagal membuat file dokumen.');
            }
            Log::info('File sementara dikonfirmasi ada, memulai proses download ke pengguna...');

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('--- TERJADI EXCEPTION FATAL SAAT GENERATE DOKUMEN ---');
            Log::error('Pesan Error: ' . $e->getMessage());
            Log::error('Lokasi File: ' . $e->getFile() . ' pada baris ' . $e->getLine());
            Log::error('Stack Trace Lengkap: ' . $e->getTraceAsString());

            // Tampilkan error 500 agar kita tahu ada masalah server, bukan 404
            abort(500, 'Terjadi kesalahan internal saat memproses dokumen Anda. Silakan cek log server.');
        }
    }

    private function terbilang($angka): string
    {
        $angka = (int) abs($angka);
        $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        $temp = "";

        if ($angka < 12) {
            $temp = " " . $huruf[$angka];
        } elseif ($angka < 20) {
            $temp = $this->terbilang($angka - 10) . " belas";
        } elseif ($angka < 100) {
            $temp = $this->terbilang(intval($angka / 10)) . " puluh" . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $temp = " seratus" . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $temp = $this->terbilang(intval($angka / 100)) . " ratus" . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $temp = " seribu" . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $temp = $this->terbilang(intval($angka / 1000)) . " ribu" . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $temp = $this->terbilang(intval($angka / 1000000)) . " juta" . $this->terbilang($angka % 1000000);
        } else {
            // Opsional: lanjutkan untuk miliar/triliun jika diperlukan
            $temp = $this->terbilang(intval($angka / 1000000000)) . " miliar" . $this->terbilang($angka % 1000000000);
        }

        return ucwords(trim($temp));
    }
}
