<?php

namespace App\Filament\Resources\KwitansiResource\Pages;

use App\Filament\Resources\KwitansiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKwitansi extends EditRecord
{
    protected static string $resource = KwitansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
