<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;

class KwitansiController extends Controller
{
    public function download(Receipt $receipt)
    {
        
        // 1. Pastikan path ke folder 'templates' (dengan 's') sudah benar.
        $templatePath = storage_path('app/template/template_kwitansi.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template kwitansi tidak ditemukan.');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        $berkas = $receipt->berkas;

        // 2. Isi semua placeholder data tunggal
        $templateProcessor->setValue('nama_pemohon', $berkas->nama_pemohon ?? 'N/A');
        $templateProcessor->setValue('jumlah', number_format((float) $receipt->amount, 0, ',', '.'));
        $templateProcessor->setValue('terbilang', $this->terbilang((float) $receipt->amount) . ' Rupiah');
        $templateProcessor->setValue('info_sertifikat', "Proses Jual Beli atas SHM No. {$berkas->sertifikat_nomor} seluas {$berkas->sertifikat_luas} m2");
        $templateProcessor->setValue('tanggal_kwitansi', \Carbon\Carbon::parse($receipt->issued_at)->translatedFormat('d F Y'));

        // 3. Logika untuk mengisi rincian biaya yang dinamis
        $rincian = $receipt->detail_biaya ?? [];

        if (count($rincian) > 0) {
            // Gunakan nama blok 'rincian' yang benar, sesuai dengan ${rincian_start}
            $templateProcessor->cloneBlock('rincian', count($rincian), true, true);

            // Isi setiap blok yang sudah disalin
            foreach ($rincian as $index => $item) {
                $templateProcessor->setValue('deskripsi_item#' . ($index + 1), $item['deskripsi'] ?? '');
                // Format jumlah item dengan pemisah ribuan
                $templateProcessor->setValue('jumlah_item#' . ($index + 1), number_format($item['jumlah'] ?? 0, 0, ',', '.'));
            }
        } else {
            // Jika tidak ada rincian, hapus placeholder blok agar tidak muncul
            $templateProcessor->deleteBlock('rincian');
        }

        // dd($templateProcessor->getVariables());

        // 4. Proses penyimpanan dan pengiriman file (sudah benar)
        $fileName = 'Kwitansi - ' . ($berkas->nama_pemohon ?? 'Tanpa Nama') . '.docx';
        $tempDirectory = storage_path('app/temp/');
        $tempFile = $tempDirectory . $fileName;

        if (!File::isDirectory($tempDirectory)) {
            File::makeDirectory($tempDirectory, 0755, true, true);
        }

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    /**
     * Fungsi helper untuk mengubah angka menjadi tulisan.
     * @param float|int $angka
     * @return string
     */
    private function terbilang($angka): string
    {
        $angka = abs($angka);
        $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        $temp = "";
        if ($angka < 12) {
            $temp = " " . $huruf[$angka];
        } else if ($angka < 20) {
            $temp = $this->terbilang($angka - 10) . " Belas";
        } else if ($angka < 100) {
            $temp = $this->terbilang($angka / 10) . " Puluh" . $this->terbilang($angka % 10);
        } else if ($angka < 200) {
            $temp = " seratus" . $this->terbilang($angka - 100);
        } else if ($angka < 1000) {
            $temp = $this->terbilang($angka / 100) . " Ratus" . $this->terbilang($angka % 100);
        } else if ($angka < 2000) {
            $temp = " seribu" . $this->terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $temp = $this->terbilang($angka / 1000) . " Ribu" . $this->terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $temp = $this->terbilang($angka / 1000000) . " Juta" . $this->terbilang($angka % 1000000);
        }
        return ucwords(trim($temp));
    }
}
