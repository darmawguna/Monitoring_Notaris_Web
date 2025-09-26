<?php

namespace App\Filament\Resources\TandaTerimaSertifikatResource\Pages;
use App\Filament\Resources\TandaTerimaSertifikatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewTandaTerimaSertifikat extends ViewRecord
{
    protected static string $resource = TandaTerimaSertifikatResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}