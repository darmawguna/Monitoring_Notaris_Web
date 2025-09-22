<?php

namespace App\Filament\Resources\TandaTerimaSertifikatResource\Pages;

use App\Filament\Resources\TandaTerimaSertifikatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTandaTerimaSertifikats extends ListRecords
{
    protected static string $resource = TandaTerimaSertifikatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
