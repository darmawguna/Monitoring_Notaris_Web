<?php

namespace App\Filament\Resources\KwitansiResource\Pages;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource;
use App\Models\Receipt;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKwitansi extends ViewRecord
{
    protected static string $resource = KwitansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Edit"
            Actions\EditAction::make()
                // Hanya tampilkan tombol Edit JIKA status BUKAN Lunas
                ->visible(fn(Receipt $record): bool => $record->status_pembayaran !== PembayaranStatus::LUNAS),

            // Tombol "Hapus" (dari logika kita sebelumnya)
            Actions\DeleteAction::make()
                ->visible(fn(): bool => auth()->user()->role->name === 'Superadmin'),
        ];
    }
}