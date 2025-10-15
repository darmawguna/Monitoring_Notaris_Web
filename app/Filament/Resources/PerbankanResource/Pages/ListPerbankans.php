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
                    $user = auth()->user();
                    return in_array($user->role->name, ['Superadmin', 'Petugas Entry']);
                }),
        ];
    }
}
