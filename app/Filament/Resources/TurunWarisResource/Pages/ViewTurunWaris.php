<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;
use App\Filament\Resources\TurunWarisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewTurunWaris extends ViewRecord
{
    protected static string $resource = TurunWarisResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}