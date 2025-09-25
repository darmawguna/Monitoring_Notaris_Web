<?php

namespace App\Http\Controllers;

use App\Models\TandaTerimaSertifikat;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use \PhpOffice\PhpWord\Element\TextRun;

class TandaTerimaController extends Controller
{
    public function download(TandaTerimaSertifikat $tandaTerimaSertifikat)
    {
        $templatePath = storage_path('app/template/template_serah_terima.docx');
        if (!file_exists($templatePath)) {
            abort(404, 'Template tanda terima tidak ditemukan.');
        }

        // Load template sebagai dokumen PhpWord
        $phpWord = IOFactory::load($templatePath);

        // Ambil data
        $penyerah = $tandaTerimaSertifikat->penyerah ?? 'N/A';
        $tanggal = $tandaTerimaSertifikat->created_at
            ? Carbon::parse($tandaTerimaSertifikat->created_at)->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        // Ganti placeholder sederhana: ${penyerah} dan ${tanggal} (termasuk typo ${ tangga l })
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                // Handle Text biasa
                if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text = $element->getText();
                    $text = str_replace('${penyerah}', $penyerah, $text);
                    $text = str_replace('${tanggal}', $tanggal, $text);
                    $text = str_replace('${tanggal}', $tanggal, $text); // <-- Perbaikan typo!
                    $element->setText($text);
                }

                // Handle TextRun (paragraf dengan formatting)
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $child) {
                        if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                            $text = $child->getText();
                            $text = str_replace('${penyerah}', $penyerah, $text);
                            $text = str_replace('${tanggal}', $tanggal, $text);
                            $text = str_replace('${ tangga l }', $tanggal, $text); // <-- Perbaikan typo!
                            $child->setText($text);
                        }
                    }
                }
            }
        }

        // Ambil dan bersihkan konten HTML dari RichEditor
        $htmlContent = $tandaTerimaSertifikat->informasi_tambahan ?? '<p>N/A</p>';
        if (trim(strip_tags($htmlContent)) === '') {
            $htmlContent = '<p>N/A</p>';
        }

        // Ambil section pertama (asumsi template hanya 1 halaman)
        $section = $phpWord->getSections()[0];
        $found = false;

        // Cari placeholder ${informasi_tambahan} di semua elemen section
        for ($i = 0; $i < count($section->getElements()); $i++) {
            $element = $section->getElements()[$i];

            // Jika elemen adalah TextRun, cek setiap child-nya
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $childIndex => $child) {
                    if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text = $child->getText();
                        if (strpos($text, '${informasi_tambahan}') !== false) {
                            // Hapus placeholder dari teks
                            $cleanText = str_replace('${informasi_tambahan}', '', $text);
                            $child->setText($cleanText);

                            // Sisipkan konten HTML DI TEMPAT ITU (setelah TextRun ini)
                            $section->insertElement($i + 1, null);
                            Html::addHtml($section, $htmlContent, false, false);
                            $found = true;
                            break 2; // Keluar dari nested loop
                        }
                    }
                }
            }

            // Jika elemen adalah Text biasa
            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text = $element->getText();
                if (strpos($text, '${informasi_tambahan}') !== false) {
                    // Hapus elemen placeholder
                    $section->removeElement($i);

                    // Sisipkan konten HTML di posisi ini
                    Html::addHtml($section, $htmlContent, false, false);
                    $found = true;
                    break;
                }
            }
        }

        // Jika tidak ditemukan, tambahkan di akhir section (fallback)
        if (!$found) {
            Html::addHtml($section, $htmlContent, false, false);
        }

        // Generate nama file
        $displayName = $tandaTerimaSertifikat->penyerah ?: 'Tanpa Nama';
        $fileName = 'Tanda Terima - ' . Str::slug($displayName) . '.docx';

        // Simpan ke folder temp
        $tempDir = storage_path('app/temp/');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true, true);
        }
        $tempPath = $tempDir . $fileName;

        // Tulis dokumen
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        // Kirim sebagai download
        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}