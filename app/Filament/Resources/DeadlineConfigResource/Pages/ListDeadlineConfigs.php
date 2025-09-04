<?php

namespace App\Filament\Resources\DeadlineConfigResource\Pages;

use App\Filament\Resources\DeadlineConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeadlineConfigs extends ListRecords
{
    protected static string $resource = DeadlineConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
