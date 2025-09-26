<?php

namespace App\Filament\Resources\PerbankanResource\Pages;
use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
class ViewPerbankan extends ViewRecord
{
    protected static string $resource = PerbankanResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}