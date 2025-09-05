<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\KwitansiResource;
use App\Models\Receipt;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;


class KwitansiTerbaru extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return 'Kwitansi Terbaru';
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Receipt::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('receipt_number')->label('Nomor Kwitansi'),
                TextColumn::make('berkas.nomor')->label('Nomor Berkas'),
                TextColumn::make('amount')->label('Total Biaya')->money('IDR'),
                TextColumn::make('berkas.total_paid')->label('Sudah Dibayar')->money('IDR'),
                TextColumn::make('issued_at')->label('Tanggal Dibuat')->date('d M Y'),
            ])
            ->actions([
                // Kita bisa menambahkan aksi di sini jika perlu
            ]);
    }
}

