<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\TugasResource;
use App\Models\DeadlineConfig;
use App\Models\User;
use App\Models\Berkas;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateBerkas extends CreateRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getFormMaxWidth(): string
    {
        return 'full';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Dapatkan tahun saat ini
        $currentYear = date('Y');
        // 2. Cari record terakhir untuk tahun ini
        $lastBerkas = Berkas::whereYear('created_at', $currentYear)->latest('id')->first();
        $nextNumber = 1; // Mulai dari 1 jika tidak ada data sebelumnya
        if ($lastBerkas) {
            // Ekstrak nomor dari format TAHUN-NOMOR (misal: 2025-001 -> 1)
            $lastNumber = (int) substr($lastBerkas->nomor_berkas, 5);
            $nextNumber = $lastNumber + 1;
        }
        // 3. Format nomor dengan 3 digit dan angka nol di depan
        $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        // 4. Gabungkan dan masukkan ke dalam data yang akan disimpan
        $data['nomor_berkas'] = $currentYear . '-' . $formattedNumber;
        $data['created_by'] = auth()->id();
        $data['status_overall'] = BerkasStatus::PROGRES;
        $data['current_stage_key'] = StageKey::PETUGAS_2;
        return $data;
    }

    protected function afterCreate(): void
    {
        $berkas = $this->getRecord();

        // Buat entri progres untuk Front Office (SELESAI)
        $berkas->progress()->create([
            'stage_key' => StageKey::FRONT_OFFICE,
            'status' => 'done',
            'assignee_id' => $berkas->created_by,
            'notes' => 'Berkas berhasil dibuat dan diteruskan.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Hitung dan simpan deadline untuk Petugas 2
        $deadlineDays = DeadlineConfig::where('stage_key', StageKey::PETUGAS_2)->value('default_days') ?? 3;
        $startedAt = now();
        $deadline = Carbon::parse($startedAt)->addDays($deadlineDays);

        // Buat entri progres untuk Petugas 2 (PENDING) dengan deadline
        $berkas->progress()->create([
            'stage_key' => StageKey::PETUGAS_2,
            'status' => 'pending',
            'assignee_id' => $berkas->current_assignee_id,
            'started_at' => $startedAt,
            'deadline' => $deadline, // Simpan deadline yang sudah dihitung
        ]);

        // Buat Kwitansi
        $berkas->receipt()->create([
            'receipt_number' => 'KW-' . $berkas->nomor,
            'amount' => $berkas->nilai_transaksi,
            'issued_at' => now(),
            'issued_by' => auth()->id(),
            'payment_method' => 'pending',
        ]);
        $berkas->update(['total_paid' => 0]);

        // Kirim notifikasi ke Petugas 2
        $petugas2 = User::find($berkas->current_assignee_id);
        if ($petugas2) {
            Notification::make()
                ->title('Anda menerima tugas baru!')
                ->body("Berkas '{$berkas->nama_berkas}' dari Front Office telah ditugaskan kepada Anda.")
                ->icon('heroicon-o-inbox-arrow-down')
                ->actions([
                    NotificationAction::make('view')
                        ->label('Lihat Tugas')
                        ->url(TugasResource::getUrl('index'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($petugas2);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berkas Berhasil Dibuat')
            ->body('Anda bisa melanjutkan untuk membuat berkas baru.');
    }
}

