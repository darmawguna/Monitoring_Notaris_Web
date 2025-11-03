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
        $user = auth()->user();
        $userRole = $user->role->name ?? null;

        // Hanya non-Superadmin & non-Petugas Entry yang tidak boleh mengubah data pihak
        if (!in_array($userRole, ['Superadmin', 'Petugas Entry'])) {
            $criticalJsonFields = ['penjual_data', 'pembeli_data', 'pihak_persetujuan_data'];
            foreach ($criticalJsonFields as $field) {
                $data[$field] = $record->getOriginal($field);
            }
        }

        // --- Tangani penugasan ---
        $newAssigneeId = $data['petugas_pengetikan_id'] ?? null;
        unset($data['petugas_pengetikan_id']);

        $pengetikanProgress = $record->progress()
            ->where('stage_key', StageKey::PETUGAS_PENGETIKAN)
            ->first();

        if ($pengetikanProgress && !is_null($newAssigneeId) && $pengetikanProgress->assignee_id != $newAssigneeId) {
            $pengetikanProgress->update(['assignee_id' => $newAssigneeId]);
        }

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
