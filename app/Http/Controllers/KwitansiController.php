<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class KwitansiController extends Controller
{
    public function download(Receipt $receipt)
    {
        // (Opsional) otorisasi jika pakai policy
        // $this->authorize('view', $receipt);

        $templatePath = storage_path('app/template/template_kwitansi.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template kwitansi tidak ditemukan.');
        }

        $doc = new TemplateProcessor($templatePath);

        // ---------- Nilai umum (terisi di dua halaman sekaligus) ----------
        $doc->setValue('nama_pemohon', $receipt->nama_pemohon_kwitansi ?? 'N/A');
        $doc->setValue('nomor_kwitansi', $receipt->receipt_number ?? 'N/A');
        $doc->setValue('notes_kwitansi', $receipt->notes_kwitansi ?? '');

        $tanggal = $receipt->issued_at
            ? Carbon::parse($receipt->issued_at)->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');
        $doc->setValue('tanggal_kwitansi', $tanggal);

        // Jika kamu punya info_sertifikat/informasi_kwitansi di DB:
        // if (property_exists($receipt, 'informasi_kwitansi')) {
        //     $doc->setValue('info_sertifikat', (string) $receipt->informasi_kwitansi);
        // }
        $doc->setValue('info_sertifikat', (string) $receipt->informasi_kwitansi);

        // ---------- Siapkan rincian ----------
        $rawItems = $receipt->detail_biaya ?? [];
        // Normalisasi & bersihkan angka
        $items = array_map(function ($it) {
            $desc = (string) ($it['deskripsi'] ?? '');
            $amt = (int) preg_replace('/\D/', '', (string) ($it['jumlah'] ?? 0));
            return ['deskripsi' => $desc, 'jumlah' => $amt];
        }, is_array($rawItems) ? $rawItems : $rawItems->toArray());

        $totalItems = array_sum(array_column($items, 'jumlah'));
        $grandTotal = count($items) ? $totalItems : (int) ($receipt->amount ?? 0);

        // ---------- Set total & terbilang (dipakai di semua tempat ${jumlah}, ${terbilang}) ----------
        $doc->setValue('jumlah', number_format($grandTotal, 0, ',', '.'));
        $doc->setValue('terbilang', $this->terbilang($grandTotal) . ' Rupiah');

        // ---------- Clone ROW untuk HALAMAN 1 ----------
        if (count($items)) {
            // Pastikan ${deskripsi_item_1} & ${jumlah_item_1} berada di SATU baris tabel
            $doc->cloneRow('deskripsi_item-1', count($items));
            foreach ($items as $i => $it) {
                $n = $i + 1;
                $doc->setValue("deskripsi_item-1#{$n}", $it['deskripsi']);
                $doc->setValue("jumlah_item-1#{$n}", number_format($it['jumlah'], 0, ',', '.'));
            }
        } else {
            // Jika tidak ada rincian, isi placeholder agar tidak kosong/invalid
            $doc->setValue('deskripsi-item-1', '-');
            $doc->setValue('jumlah_item-1', '0');
        }

        // ---------- Clone ROW untuk HALAMAN 2 ----------
        if (count($items)) {
            // Pastikan ${deskripsi_item_2} & ${jumlah_item_2} berada di SATU baris tabel
            $doc->cloneRow('deskripsi_item-2', count($items));
            foreach ($items as $i => $it) {
                $n = $i + 1;
                $doc->setValue("deskripsi_item-2#{$n}", $it['deskripsi']);
                $doc->setValue("jumlah_item-2#{$n}", number_format($it['jumlah'], 0, ',', '.'));
            }
        } else {
            $doc->setValue('deskripsi_item-2', '-');
            $doc->setValue('jumlah_item-2', '0');
        }

        // ---------- Simpan & kirim ----------
        $displayName = $receipt->nama_pemohon_kwitansi ?: 'Tanpa Nama';
        $fileName = 'Kwitansi - ' . Str::slug($displayName) . '.docx';

        $tempDir = storage_path('app/temp/');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $tempPath = $tempDir . $fileName;

        $doc->saveAs($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
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
