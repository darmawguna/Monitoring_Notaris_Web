<?php

namespace App\Filament\Resources\Berkas\Pages;

use App\Filament\Resources\Berkas\BerkasResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\BerkasStatus;
use App\Enums\StageKey;


class CreateBerkas extends CreateRecord
{
    protected static string $resource = BerkasResource::class;
    protected function getFormMaxWidth(): string
    {
        return 'full'; // <-- Mengatur lebar form menjadi full
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Set siapa yang membuat berkas
        $data['created_by'] = auth()->id();

        // 2. Set status awal berkas
        $data['status_overall'] = BerkasStatus::PROGRES;

        // 3. Set TAHAP SELANJUTNYA (sesuai permintaan Anda)
        $data['current_stage_key'] = StageKey::PETUGAS_2;
        // 4. (Opsional tapi direkomendasikan) Set siapa assignee pertama
        // Logika ini bisa lebih kompleks, misalnya mencari user dengan peran Petugas2
        // Untuk saat ini kita bisa kosongkan dulu
        // $data['current_assignee_id'] = ... 
        return $data;
    }

}
