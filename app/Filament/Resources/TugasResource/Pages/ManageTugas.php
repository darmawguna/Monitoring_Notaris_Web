<?php

namespace App\Filament\Resources\TugasResource\Pages;

use App\Filament\Resources\TugasResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTugas extends ManageRecords
{
    protected static string $resource = TugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
