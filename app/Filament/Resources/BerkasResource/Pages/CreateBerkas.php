<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBerkas extends CreateRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getFormMaxWidth(): string
    {
        return 'full';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status_overall'] = BerkasStatus::PROGRES;
        $data['current_stage_key'] = StageKey::PETUGAS_2;

        return $data;
    }
}
