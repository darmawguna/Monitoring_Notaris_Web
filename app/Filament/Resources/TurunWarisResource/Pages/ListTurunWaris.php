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
            Actions\CreateAction::make(),
        ];
    }
}
