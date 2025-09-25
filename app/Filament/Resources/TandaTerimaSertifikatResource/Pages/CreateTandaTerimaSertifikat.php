<?php

namespace App\Filament\Resources\TandaTerimaSertifikatResource\Pages;

use App\Filament\Resources\TandaTerimaSertifikatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTandaTerimaSertifikat extends CreateRecord
{
    protected static string $resource = TandaTerimaSertifikatResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
