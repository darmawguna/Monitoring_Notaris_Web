<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBerkas extends EditRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function canEdit(Model $record): bool
    {
        // Hanya izinkan edit jika peran pengguna adalah Superadmin.
        return auth()->user()->role->name === 'Superadmin';
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Dapatkan record Berkas yang sedang diedit
        $berkas = $this->getRecord();

        // 2. Cari catatan progres untuk tahap "Pengetikan" (atau Petugas 2, sesuaikan jika perlu)
        $pengetikanProgress = $berkas->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN) // Ganti dengan StageKey yang benar jika berbeda
            ->first();

        // 3. Jika catatan progres ditemukan, ambil ID petugasnya
        if ($pengetikanProgress) {
            // "Suntikkan" ID petugas ke dalam data form agar dropdown terisi
            $data['petugas_pengetikan_id'] = $pengetikanProgress->assignee_id;
        }

        return $data;
    }

    /**
     * Metode ini berjalan SEBELUM data disimpan ke database.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Ambil ID petugas yang baru dari form
        $newAssigneeId = $data['petugas_pengetikan_id'];

        // 2. Hapus kunci ini dari data agar tidak mencoba disimpan ke tabel 'berkas'
        unset($data['petugas_pengetikan_id']);

        // 3. Cari catatan progres yang relevan
        $pengetikanProgress = $record->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN) // Ganti dengan StageKey yang benar
            ->first();

        // 4. Jika ada dan ID petugas berubah, perbarui
        if ($pengetikanProgress && $pengetikanProgress->assignee_id != $newAssigneeId) {
            $pengetikanProgress->update(['assignee_id' => $newAssigneeId]);
            // Di sini Anda bisa menambahkan logika notifikasi jika penugasan berubah
        }

        // 5. Lanjutkan proses update untuk sisa data di tabel 'berkas'
        $record->update($data);

        return $record;
    }

}
