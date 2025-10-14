<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TurunWarisResource;
use App\Models\TurunWaris;
use Filament\Tables\Columns\TextColumn;

class TugasTurunWarisMasukWidget extends TugasMasukWidget
{
    protected static ?int $sort = 3;

    protected static string $model = TurunWaris::class;
    protected static string $title = 'Tugas Berkas Turun Waris Masuk';

    public static function getResource(): string
    {
        return TurunWarisResource::class;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nama_kasus')->label('Nama Kasus'),
            TextColumn::make('created_at')->label('Tanggal Masuk')->since(),
        ];
    }
}
