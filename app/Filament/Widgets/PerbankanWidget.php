<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PerbankanResource;
use App\Models\Perbankan;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PerbankanWidget extends BaseWidget
{
    // 1. UBAH SORT MENJADI 1 UNTUK MEMINDAHKANNYA KE ATAS
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    public function getTableHeading(): string
    {
        // Ganti judul agar lebih sesuai
        return 'Deadline Covernote Perbankan Terdekat';
    }

    // 2. PERBARUI LOGIKA QUERY SECARA TOTAL
    protected function getTableQuery(): Builder
    {
        // Dapatkan tanggal hari ini
        $today = Carbon::today();

        // Buat query yang menghitung tanggal deadline secara dinamis
        return Perbankan::query()
            // Pilih semua kolom asli, dan tambahkan kolom virtual 'deadline_date'
            ->select(
                'perbankans.*', // Pilih semua kolom dari tabel perbankans
                DB::raw('DATE_ADD(tanggal_covernote, INTERVAL jangka_waktu MONTH) as deadline_date')
            )
            // Filter: hanya tampilkan yang belum kedaluwarsa
            ->whereRaw('DATE_ADD(tanggal_covernote, INTERVAL jangka_waktu MONTH) >= ?', [$today])
            // Urutkan berdasarkan deadline terdekat
            ->orderBy('deadline_date', 'asc')
            // Batasi hanya 5 hasil
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('nama_debitur')
                ->label('Nama Debitur')
                ->searchable(),

            TextColumn::make('nama_kreditur')
                ->label('Nama Kreditur'),

            TextColumn::make('deadline_date')
                ->label('Tanggal Deadline')
                ->date('d M Y')
                ->sortable(), // Sekarang bisa diurutkan

            // 3. TAMBAHKAN KOLOM "SISA HARI" YANG DINAMIS
            TextColumn::make('sisa_hari')
                ->label('Sisa Hari')
                ->state(function (Perbankan $record): int {
                    // Hitung tanggal deadline menggunakan Carbon
                    $deadline = Carbon::parse($record->tanggal_covernote)->addMonths($record->jangka_waktu);
                    // Hitung selisih hari dari sekarang (false agar bisa negatif jika terlewat)
                    return Carbon::now()->diffInDays($deadline, false);
                })
                ->formatStateUsing(fn (int $state): string => $state >= 0 ? $state . ' hari lagi' : 'Terlewat ' . ($state * -1) . ' hari')
                // Beri warna 'danger' jika sisa hari <= 7
                ->color(fn (int $state): string => $state <= 7 ? 'danger' : 'primary'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->url(fn(Perbankan $record): string => PerbankanResource::getUrl('view', ['record' => $record])),
        ];
    }
}

