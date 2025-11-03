<?php

namespace App\Filament\Resources\KwitansiResource\Pages;

use App\Filament\Resources\KwitansiResource;
use Filament\Actions;
use App\Models\Receipt;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateKwitansi extends CreateRecord
{
    protected static string $resource = KwitansiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Logika penomoran otomatis: nomor/bulan/tahun
        $month = date('n');
        $year = date('Y');

        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $romanMonth = $romanMonths[$month - 1];

        $lastReceipt = Receipt::whereYear('created_at', $year)
            // hapus whereMonth jika ingin reset tiap tahun
            ->whereMonth('created_at', $month)
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($lastReceipt && Str::contains($lastReceipt->receipt_number, '/')) {
            $lastNumber = (int) explode('/', $lastReceipt->receipt_number)[0];
            $nextNumber = $lastNumber + 1;
        }

        $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $data['receipt_number'] = "{$formattedNumber}/{$romanMonth}/{$year}";
        $data['issued_by'] = auth()->id();
        $data['issued_at'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
