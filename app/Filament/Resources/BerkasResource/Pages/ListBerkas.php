<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBerkas extends ListRecords
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                // --- PERUBAHAN DI SINI ---
                // Tambahkan metode ->visible() dengan logika otorisasi
                ->visible(function (): bool {
                    // Dapatkan pengguna yang sedang login
                    $user = auth()->user();
                    // Kembalikan 'true' (tampilkan tombol) hanya jika peran pengguna
                    // adalah 'Superadmin' atau 'FrontOffice'.
                    // Catatan: Saya mengasumsikan 'petugasentry' adalah 'FrontOffice'.
                    // Anda bisa mengubahnya jika nama perannya berbeda.
                    return in_array($user->role->name, ['Superadmin', 'FrontOffice']);
                }),
        ];
    }
}
