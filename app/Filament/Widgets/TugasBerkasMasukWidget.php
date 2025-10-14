<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BerkasResource;
use App\Models\Berkas;
use Filament\Tables\Columns\TextColumn;

class TugasBerkasMasukWidget extends TugasMasukWidget
{
    protected static ?int $sort = 1;

    // Tentukan model dan judul spesifik untuk widget ini
    protected static string $model = Berkas::class;
    protected static string $title = 'Tugas Berkas Peralihan Hak Masuk';

    // Tentukan resource yang sesuai untuk tombol "View"
    public static function getResource(): string
    {
        return BerkasResource::class;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nomor_berkas')->label('Nomor Berkas'),
            TextColumn::make('nama_pemohon')->label('Nama Pemohon'),
            TextColumn::make('created_at')->label('Tanggal Masuk')->since(),
        ];
    }
}
