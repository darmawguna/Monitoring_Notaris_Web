<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Filament\Resources\TugasResource;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Support\Facades\Log;

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
        $berkas->progress()->create([
            'stage_key' => StageKey::PETUGAS_2,
            'status' => 'pending', // Statusnya masih menunggu
            'assignee_id' => $berkas->current_assignee_id,
            'started_at' => now(), // Tugas dimulai sekarang
        ]);
        // --- INI LOGIKA BARU ---
        // 2. Buat Kwitansi secara otomatis
        $berkas->receipt()->create([
            'receipt_number' => 'KW-' . $berkas->nomor, // Gunakan nomor berkas agar unik
            'amount' => $berkas->total_cost, // Jumlah di kwitansi = total biaya
            'issued_at' => now(),
            'issued_by' => auth()->id(),
            'payment_method' => 'pending', // Status awal pembayaran
        ]);

        // 3. Set total_paid awal menjadi 0
        $berkas->update(['total_paid' => 0]);
        // dd($berkas->toArray());

        // --- INI LOGIKA NOTIFIKASI BARU ---
        // 3. Kirim notifikasi ke Petugas 2 yang ditugaskan
        Log::info('--- Memulai proses afterCreate ---');

        $berkas = $this->getRecord();

        // Log 1: Periksa data berkas yang baru saja dibuat
        Log::info('Data Berkas:', $berkas->toArray());

        // Log 2: Periksa ID assignee yang kita dapatkan
        $assigneeId = $berkas->current_assignee_id;
        Log::info("Mencoba mencari User dengan ID: {$assigneeId}");

        // Log 3: Cari pengguna
        $petugas2 = User::find($assigneeId);

        // Log 4: Periksa apakah pengguna ditemukan
        if ($petugas2) {
            Log::info('Pengguna ditemukan:', $petugas2->toArray());

            // Logika notifikasi Anda
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

            // Log 5: Konfirmasi bahwa pengiriman notifikasi telah dicoba
            Log::info('--- Pengiriman notifikasi ke database TELAH DICOBA. ---');

        } else {
            // Log 6: Jika pengguna tidak ditemukan, ini akan tercatat
            Log::info('!!! PENGGUNA TIDAK DITEMUKAN. Notifikasi dibatalkan. !!!');
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
