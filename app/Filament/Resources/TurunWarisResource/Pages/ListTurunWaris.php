<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;

use App\Filament\Resources\TurunWarisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTurunWaris extends ListRecords
{
    protected static string $resource = TurunWarisResource::class;

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
                    return in_array($user->role->name, ['Superadmin', 'FrontOffice']);
                }),
        ];
    }
}
