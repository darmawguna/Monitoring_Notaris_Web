<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BerkasResource;
use App\Models\Berkas;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class BerkasTerbaru extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;
    /**
     * Pastikan widget ini hanya terlihat oleh Superadmin.
     */
    public static function canView(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    public function getTableHeading(): string
    {
        // Judul bisa dibuat lebih spesifik
        return 'Berkas Jual Beli Terbaru';
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            // Praktik terbaik untuk widget dasbor adalah membatasi jumlah data
            ->query(Berkas::query()->limit(5))
            ->defaultSort('created_at', 'desc')
            ->columns([
                // --- INI BAGIAN YANG DIPERBARUI ---

                // 1. Ganti 'nomor' menjadi 'nomor_berkas'
                TextColumn::make('nomor_berkas')
                    ->label('Nomor Berkas')
                    ->searchable(),

                TextColumn::make('nama_berkas')
                    ->searchable(),

                // 2. Ganti 'penjual' dengan 'nama_pemohon'
                TextColumn::make('nama_pemohon')
                    ->searchable(),

                // 3. Tampilkan nama pembeli dari dalam data JSON
                TextColumn::make('pembeli_data.nama')
                    ->label('Pembeli'),

                BadgeColumn::make('current_stage_key')
                    ->label('Tahap Saat Ini'),

                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y'),
                // --- AKHIR DARI PERUBAHAN ---
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Berkas $record): string => BerkasResource::getUrl('view', ['record' => $record])),
            ]);
    }
}