<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerbankans extends ListRecords
{
    protected static string $resource = PerbankanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(function (): bool {
                    // Dapatkan pengguna yang sedang login
                    $user = auth()->user();
                    // Kembalikan 'true' (tampilkan tombol) hanya jika peran pengguna
                    // adalah 'Superadmin' atau 'FrontOffice'.
                    // Catatan: Saya mengasumsikan 'petugasentry' adalah 'FrontOffice'.
                    // Anda bisa mengubahnya jika nama perannya berbeda.
                    return in_array($user->role->name, ['Superadmin', 'Petugas Entry']);
                }),
        ];
    }
}
