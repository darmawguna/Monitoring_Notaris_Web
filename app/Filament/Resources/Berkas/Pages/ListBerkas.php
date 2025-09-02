<?php

namespace App\Filament\Resources\Berkas\Pages;

use App\Filament\Resources\Berkas\BerkasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBerkas extends ListRecords
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
