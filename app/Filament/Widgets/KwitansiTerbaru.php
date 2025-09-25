<?php

namespace App\Filament\Widgets;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource;
use App\Models\Receipt;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class KwitansiTerbaru extends BaseWidget
{
    protected static ?int $sort = 4;
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
        return 'Transaksi Kwitansi Terbaru';
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            // Praktik terbaik: batasi jumlah data di widget dasbor
            ->query(Receipt::query()->limit(5))
            ->defaultSort('created_at', 'desc')
            ->columns([
                // --- INI BAGIAN YANG DIPERBARUI ---

                // 1. Ganti 'berkas.nomor' dengan 'berkas.nomor_berkas' dan 'berkas.nama_pemohon'
                TextColumn::make('receipt_number')
                    ->label('Nomor Kwitansi')
                    ->searchable(),

                TextColumn::make('berkas.nama_pemohon')
                    ->label('Nama Pemohon')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR'),

                // 2. Tambahkan kolom status pembayaran
                BadgeColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->colors([
                        'success' => PembayaranStatus::LUNAS->value,
                        'danger' => PembayaranStatus::BELUM_LUNAS->value,
                    ]),

                TextColumn::make('issued_at')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y'),
                // --- AKHIR DARI PERUBAHAN ---
            ])
            ->actions([
                // Tambahkan link untuk melihat detail di KwitansiResource
                Tables\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url(fn(Receipt $record): string => KwitansiResource::getUrl('index')),
            ]);
    }
}