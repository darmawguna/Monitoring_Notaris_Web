<?php

namespace App\Filament\Resources\Berkas\Pages;

use App\Filament\Resources\Berkas\BerkasResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBerkas extends ViewRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
