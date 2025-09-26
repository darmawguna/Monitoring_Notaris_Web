<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerbankan extends EditRecord
{
    protected static string $resource = PerbankanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
