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
    protected function afterCreate(): void
    {
        // Dapatkan record Berkas yang baru saja dibuat
        $berkas = $this->getRecord();

        // Buat entri pertama di tabel 'progress' melalui relasi
        $berkas->progress()->create([
            'stage_key' => StageKey::FRONT_OFFICE, // Tahapan yang BARU SAJA SELESAI
            'status' => 'done',
            'assignee_id' => $berkas->created_by, // Petugas yang menyelesaikan adalah pembuatnya
            'notes' => 'Berkas berhasil dibuat dan diteruskan.',
            'started_at' => now(),
            'completed_at' => now(), // Karena proses ini instan
        ]);
    }
}
