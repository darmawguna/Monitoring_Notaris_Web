<?php

namespace App\Filament\Widgets;

use App\Enums\BerkasStatus;
use App\Models\Berkas;
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
        return auth()->user()->role->name === 'Superadmin';
    }

    protected function getStats(): array
    {
        // 1. Lakukan kalkulasi data dari database
        $totalBerkas = Berkas::count();
        $berkasDiproses = Berkas::where('status_overall', BerkasStatus::PROGRES)->count();
        $berkasSelesai = Berkas::where('status_overall', BerkasStatus::SELESAI)->count();

        // 2. Buat "kartu" statistik
        return [
            Stat::make('Total Berkas', $totalBerkas)
                ->description('Jumlah seluruh berkas yang tercatat')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray'),

            Stat::make('Berkas Sedang Diproses', $berkasDiproses)
                ->description('Berkas yang masih aktif dalam alur kerja')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Berkas Selesai', $berkasSelesai)
                ->description('Berkas yang telah selesai seluruh tahapannya')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}