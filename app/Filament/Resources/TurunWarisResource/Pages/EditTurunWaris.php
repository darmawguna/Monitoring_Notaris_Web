<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;

use App\Filament\Resources\TurunWarisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTurunWaris extends EditRecord
{
    protected static string $resource = TurunWarisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
