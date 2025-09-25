<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePerbankan extends CreateRecord
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
}
