<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PerbankanResource;
use App\Models\Perbankan;
use Filament\Tables\Columns\TextColumn;

class TugasPerbankanMasukWidget extends TugasMasukWidget
{
    protected static ?int $sort = 2;

    protected static string $model = Perbankan::class;
    protected static string $title = 'Tugas Berkas Perbankan Masuk';

    public static function getResource(): string
    {
        return PerbankanResource::class;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nama_debitur')->label('Nama Debitur'),
            TextColumn::make('nama_kreditur')->label('Nama Kreditur'),
            TextColumn::make('created_at')->label('Tanggal Masuk')->since(),
        ];
    }
}
