<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Enums\StageKey;
use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPerbankan extends EditRecord
{
    protected static string $resource = PerbankanResource::class;

    /**
     * Metode ini berjalan SEBELUM form diisi dengan data.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Dapatkan record Perbankan yang sedang diedit
        $perbankan = $this->getRecord();

        // 2. Cari catatan progres untuk tahap awal (Petugas Pengetikan)
        // PASTIKAN 'StageKey::PETUGAS_PENGETIKAN' SESUAI DENGAN ENUM ANDA
        $initialProgress = $perbankan->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN)
            ->first();

        // 3. Jika catatan progres ditemukan, ambil ID petugasnya
        if ($initialProgress) {
            // "Suntikkan" ID petugas ke dalam data form agar dropdown terisi
            $data['petugas_pengetikan_id'] = $initialProgress->assignee_id;
        }

        return $data;
    }

    /**
     * Metode ini berjalan SEBELUM data disimpan ke database.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Ambil ID petugas yang baru dari form (jika ada)
        $newAssigneeId = $data['petugas_pengetikan_id'] ?? null;

        // 2. Hapus kunci ini dari data agar tidak mencoba disimpan ke tabel 'perbankans'
        unset($data['petugas_pengetikan_id']);

        // 3. Logika untuk "Jangka Waktu Lainnya" (dari kode Anda)
        if (isset($data['jangka_waktu']) && $data['jangka_waktu'] == 0 && isset($data['jangka_waktu_lainnya'])) {
            $data['jangka_waktu'] = $data['jangka_waktu_lainnya'];
        }
        unset($data['jangka_waktu_lainnya']);

        // 4. Cari catatan progres yang relevan
        $initialProgress = $record->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN) // Ganti ini jika StageKey Anda berbeda
            ->first();

        // 5. Jika ada dan ID petugas berubah, perbarui
        if ($initialProgress && $newAssigneeId && $initialProgress->assignee_id != $newAssigneeId) {
            $initialProgress->update(['assignee_id' => $newAssigneeId]);
            // Anda bisa menambahkan logika notifikasi di sini jika penugasan berubah
        }

        // 6. Lanjutkan proses update untuk sisa data di tabel 'perbankans'
        $record->update($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tampilkan tombol Hapus (hanya untuk Superadmin)
            Actions\DeleteAction::make()
                ->visible(fn(): bool => auth()->user()->role->name === 'Superadmin'),
        ];
    }
}