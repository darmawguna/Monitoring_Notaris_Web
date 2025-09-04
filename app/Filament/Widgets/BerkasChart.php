<?php

namespace App\Filament\Widgets;

use App\Enums\StageKey;
use App\Models\Berkas;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BerkasChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Berkas per Tahapan';
    protected static ?int $sort = 2; // Tampilkan di bawah widget statistik

    /**
     * Tentukan apakah widget ini boleh dilihat.
     * Hanya Superadmin yang bisa melihat widget ini.
     */
    public static function canView(): bool
    {
        return auth()->user()->role->name === 'FrontOffice';
    }

    protected function getData(): array
    {
        // 1. Query data untuk chart
        $data = Berkas::query()
            ->select('current_stage_key', DB::raw('count(*) as count'))
            // Hanya hitung berkas yang sedang diproses
            ->whereIn('current_stage_key', [
                StageKey::PETUGAS_2->value,
                StageKey::PAJAK->value,
                StageKey::PETUGAS_5->value,
            ])
            ->groupBy('current_stage_key')
            ->pluck('count', 'current_stage_key')
            ->all();

        // 2. Siapkan data untuk ditampilkan di chart
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Berkas',
                    'data' => [
                        $data[StageKey::PETUGAS_2->value] ?? 0,
                        $data[StageKey::PAJAK->value] ?? 0,
                        $data[StageKey::PETUGAS_5->value] ?? 0,
                    ],
                    'backgroundColor' => [
                        'rgba(251, 146, 60, 0.5)', // Orange
                        'rgba(239, 68, 68, 0.5)',  // Red
                        'rgba(34, 197, 94, 0.5)', // Green
                    ],
                    'borderColor' => [
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)',
                        'rgb(34, 197, 94)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => [
                'Petugas 2',
                'Pajak',
                'Petugas 5',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Tipe chart adalah diagram batang
    }
}