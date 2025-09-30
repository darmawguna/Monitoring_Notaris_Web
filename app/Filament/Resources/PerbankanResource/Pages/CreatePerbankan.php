<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\PerbankanResource;
use App\Filament\Resources\TugasResource;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Models\DeadlineConfig;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
class CreatePerbankan extends CreateRecord
{
    protected static string $resource = PerbankanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Pisahkan data petugas dari data utama
        $petugas2Id = $data['petugas_2_id'];
        unset($data['petugas_2_id']);

        // Set tahap awal saat berkas dibuat
        $data['current_stage_key'] = StageKey::PETUGAS_2;

        return static::getModel()::create($data);
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['jangka_waktu']) && $data['jangka_waktu'] == 0 && isset($data['jangka_waktu_lainnya'])) {
            $data['jangka_waktu'] = $data['jangka_waktu_lainnya'];
        }
        unset($data['jangka_waktu_lainnya']);

        // Tetapkan field-field default untuk record Perbankan
        $data['created_by'] = auth()->id();
        $data['status_overall'] = BerkasStatus::PROGRES;
        $data['current_stage_key'] = StageKey::PETUGAS_2;

        // Kembalikan data yang sudah siap untuk disimpan ke tabel 'perbankans'
        return $data;
    }

    protected function afterCreate(): void
    {
        // 2. Buat catatan progres "done" untuk Front Office
        $this->record->progress()->create([
            'stage_key' => StageKey::FRONT_OFFICE,
            'status' => 'done',
            'notes' => 'Berkas Perbankan diterima oleh Front Office.',
            'assignee_id' => auth()->id(),
            'completed_at' => now(),
        ]);
        // Deploy
        $deadlineDays = DeadlineConfig::where('stage_key', StageKey::PETUGAS_2)->value('default_days') ?? 3;
        $startedAt = now();
        $deadline = Carbon::parse($startedAt)->addDays($deadlineDays);


        // 3. Buat catatan progres "pending" untuk Petugas 2
        $this->record->progress()->create([
            'stage_key' => StageKey::PETUGAS_2,
            'status' => 'pending',
            'notes' => 'Berkas ditugaskan ke Petugas 2.',
            'assignee_id' => $this->data['petugas_2_id'],
            'deadline' => $deadline
        ]);

        // 4. Kirim notifikasi ke Petugas 2
        $petugas2 = User::find($this->data['petugas_2_id']);
        if ($petugas2) {
            Notification::make()
                ->title('Anda menerima tugas Perbankan baru!')
                ->body("Berkas '{$this->record->nama_debitur}' telah ditugaskan kepada Anda.")
                ->icon('heroicon-o-building-office-2')
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