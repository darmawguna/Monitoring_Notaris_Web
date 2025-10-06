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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Dapatkan record Perbankan yang sedang diedit
        $perbankan = $this->getRecord();

        // 2. Cari catatan progres untuk tahap awal (Petugas Pengetikan/Petugas 2)
        $initialProgress = $perbankan->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN) // Ganti ini jika StageKey Anda berbeda
            ->first();

        // 3. Jika catatan progres ditemukan, ambil ID petugasnya
        if ($initialProgress) {
            // "Suntikkan" ID petugas ke dalam data form agar dropdown terisi
            $data['petugas_pengetikan_id'] = $initialProgress->assignee_id;
        }
        // Logika untuk menangani "Lainnya"
        if (isset($data['jangka_waktu']) && $data['jangka_waktu'] == 0 && isset($data['jangka_waktu_lainnya'])) {
            // Ganti nilai 'jangka_waktu' dengan nilai dari input 'lainnya'
            $data['jangka_waktu'] = $data['jangka_waktu_lainnya'];
        }

        // Hapus field sementara agar tidak coba disimpan ke database
        unset($data['jangka_waktu_lainnya']);

        $data['created_by'] = auth()->id();

        
        return $data;
    }

    protected function afterSave()
    {
        $filePath = $this->data['berkas_bank']; // Ambil path dari data form

        if ($filePath) {
            $this->record->file()->create([
                'path' => $filePath,
                'type' => 'berkas_bank', // Tandai jenis filenya
            ]);
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Ambil ID petugas yang baru dari form (jika ada)
        $newAssigneeId = $data['petugas_pengetikan_id'] ?? null;

        // 2. Hapus kunci ini dari data agar tidak mencoba disimpan ke tabel 'perbankans'
        unset($data['petugas_pengetikan_id']);

        // 3. Cari catatan progres yang relevan
        $initialProgress = $record->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN) // Ganti ini jika StageKey Anda berbeda
            ->first();

        // 4. Jika ada dan ID petugas berubah, perbarui
        if ($initialProgress && $newAssigneeId && $initialProgress->assignee_id != $newAssigneeId) {
            $initialProgress->update(['assignee_id' => $newAssigneeId]);
            // Anda bisa menambahkan logika notifikasi di sini jika penugasan berubah
        }

        // 5. Lanjutkan proses update untuk sisa data di tabel 'perbankans'
        $record->update($data);

        return $record;
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
