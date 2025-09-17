<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;

use App\Filament\Resources\TurunWarisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTurunWaris extends CreateRecord
{
    protected static string $resource = TurunWarisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}