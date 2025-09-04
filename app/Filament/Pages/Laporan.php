<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BerkasChart;
use App\Filament\Widgets\BerkasTerbaru;
use App\Filament\Widgets\KwitansiTerbaru;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Page;

class Laporan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.laporan';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Terpusat';

    protected static ?string $navigationLabel = 'Laporan';

    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            BerkasChart::class,
            
        ];
    }
}

