<?php

namespace App\Filament\Resources\DeadlineConfigResource\Pages;

use App\Filament\Resources\DeadlineConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeadlineConfig extends EditRecord
{
    protected static string $resource = DeadlineConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
