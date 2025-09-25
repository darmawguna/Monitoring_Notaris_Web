<?php

namespace App\Filament\Resources\TandaTerimaSertifikatResource\Pages;

use App\Filament\Resources\TandaTerimaSertifikatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTandaTerimaSertifikat extends EditRecord
{
    protected static string $resource = TandaTerimaSertifikatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
