<?php

namespace App\Filament\Resources\TandaTerimaSertifikatResource\Pages;

use App\Filament\Resources\TandaTerimaSertifikatResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use App\Models\TandaTerimaSertifikat;
class CreateTandaTerimaSertifikat extends CreateRecord
{
    protected static string $resource = TandaTerimaSertifikatResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Dapatkan bulan dan tahun saat ini
        $month = date('n');
        $year = date('Y');

        // 2. Konversi bulan ke angka Romawi
        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $romanMonth = $romanMonths[$month - 1];

        // 3. Cari record terakhir untuk bulan dan tahun ini untuk mendapatkan nomor urut berikutnya
        $lastRecord = TandaTerimaSertifikat::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && Str::contains($lastRecord->nomor_berkas, '/')) {
            $lastNumber = (int) explode('/', $lastRecord->nomor_berkas)[0];
            $nextNumber = $lastNumber + 1;
        }

        // 4. Format nomor dengan 3 digit (misal: 001, 002)
        $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // 5. Gabungkan dan masukkan ke dalam data yang akan disimpan
        $data['nomor_berkas'] = "{$formattedNumber}/{$romanMonth}/{$year}";

        // Logika `created_by` Anda yang sudah ada
        $data['created_by'] = auth()->id();
        return $data;
    }
}
