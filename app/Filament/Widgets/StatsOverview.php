<?php

namespace App\Filament\Widgets;

use App\Enums\BerkasStatus;
use App\Models\Berkas;
use App\Models\Perbankan; // <-- 1. Tambahkan model Perbankan
use App\Models\TandaTerimaSertifikat; // <-- 2. Tambahkan model TandaTerima
use App\Models\TurunWaris; // <-- 3. Tambahkan model TurunWaris
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    /**
     * Tentukan apakah widget ini boleh dilihat.
     * Hanya Superadmin yang bisa melihat widget ini.
     */
    public static function canView(): bool
    {
        // Logika ini sudah benar, tidak perlu diubah
        return auth()->user()->role->name === 'Superadmin';
    }

    protected function getStats(): array
    {
        // 1. Hitung Total Berkas (dari SEMUA model)
        $totalBerkas = Berkas::count();
        $totalPerbankan = Perbankan::count();
        $totalTurunWaris = TurunWaris::count();
        $totalTandaTerima = TandaTerimaSertifikat::count(); // TandaTerima tetap dihitung di total

        $grandTotal = $totalBerkas + $totalPerbankan + $totalTurunWaris + $totalTandaTerima;

        // --- PERBAIKAN DI SINI ---

        // 2. Hitung Berkas Sedang Diproses (HANYA dari model yang memiliki alur kerja)
        $berkasDiproses = Berkas::where('status_overall', BerkasStatus::PROGRES)->count();
        $perbankanDiproses = Perbankan::where('status_overall', BerkasStatus::PROGRES)->count();
        $turunWarisDiproses = TurunWaris::where('status_overall', BerkasStatus::PROGRES)->count();
        // $tandaTerimaDiproses Dihapus karena tidak memiliki alur kerja

        $totalDiproses = $berkasDiproses + $perbankanDiproses + $turunWarisDiproses;

        // 3. Hitung Berkas Selesai (HANYA dari model yang memiliki alur kerja)
        $berkasSelesai = Berkas::where('status_overall', BerkasStatus::SELESAI)->count();
        $perbankanSelesai = Perbankan::where('status_overall', BerkasStatus::SELESAI)->count();
        $turunWarisSelesai = TurunWaris::where('status_overall', BerkasStatus::SELESAI)->count();
        // $tandaTerimaSelesai Dihapus karena tidak memiliki alur kerja

        $totalSelesai = $berkasSelesai + $perbankanSelesai + $turunWarisSelesai;


        return [
            Stat::make('Total Berkas', $grandTotal)
                ->description('Jumlah seluruh berkas dari semua modul')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray'),

            Stat::make('Berkas Sedang Diproses', $totalDiproses)
                ->description('Berkas yang masih aktif dalam alur kerja')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Berkas Selesai', $totalSelesai)
                ->description('Berkas yang telah selesai seluruh tahapannya')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}

