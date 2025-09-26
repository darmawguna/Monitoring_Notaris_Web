<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\TugasResource;
use App\Filament\Resources\TurunWarisResource;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTurunWaris extends CreateRecord
{
    protected static string $resource = TurunWarisResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Ambil ID petugas, lalu hapus dari data utama
        $petugas2Id = $data['petugas_2_id'];
        unset($data['petugas_2_id']);

        // 2. Tetapkan status dan tahap awal
        $data['status_overall'] = BerkasStatus::PROGRES;
        $data['current_stage_key'] = StageKey::PETUGAS_2;

        // 3. Panggil metode create() dari parent
        return static::getModel()::create($data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Buat catatan progres "done" untuk Front Office
        $this->record->progress()->create([
            'stage_key' => StageKey::FRONT_OFFICE,
            'status' => 'done',
            'notes' => 'Berkas Turun Waris diterima oleh Front Office.',
            'assignee_id' => auth()->id(),
            'completed_at' => now(),
        ]);

        // 2. Buat catatan progres "pending" untuk Petugas 2
        $this->record->progress()->create([
            'stage_key' => StageKey::PETUGAS_2,
            'status' => 'pending',
            'notes' => 'Berkas ditugaskan ke Petugas 2.',
            'assignee_id' => $this->data['petugas_2_id'], // Ambil ID dari form
        ]);

        // 3. Kirim notifikasi ke Petugas 2
        $petugas2 = User::find($this->data['petugas_2_id']);
        if ($petugas2) {
            Notification::make()
                ->title('Anda menerima tugas Turun Waris baru!')
                ->body("Berkas '{$this->record->nama_kasus}' telah ditugaskan kepada Anda.")
                ->icon('heroicon-o-users')
                ->actions([
                    NotificationAction::make('view')
                        ->label('Lihat Tugas')
                        ->url(TugasResource::getUrl('index'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($petugas2);
        }
    }
}
