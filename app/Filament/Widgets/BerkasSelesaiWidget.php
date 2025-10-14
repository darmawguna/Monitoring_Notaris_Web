<?php

namespace App\Filament\Widgets;

use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\PerbankanResource;
use App\Filament\Resources\TandaTerimaSertifikatResource;
use App\Filament\Resources\TurunWarisResource;
use App\Models\Berkas;
use App\Models\Perbankan;
use App\Models\Progress;
use App\Models\TandaTerimaSertifikat;
use App\Models\TurunWaris;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BerkasSelesaiWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static bool $isLazy = false;

    // Widget ini hanya untuk 'Petugas Entry'
    public static function canView(): bool
    {
        return auth()->user()->role->name === 'Petugas Entry';
    }

    public function getTableHeading(): string
    {
        return 'Berkas Selesai Siap Diserahkan';
    }

    protected function getTableQuery(): Builder
    {
        // Ambil semua tugas 'PENYERAHAN' yang ditugaskan ke pengguna ini
        return Progress::query()
            ->where('assignee_id', auth()->id())
            ->where('status', 'pending')
            ->where('stage_key', StageKey::PENYERAHAN)
            ->with(['progressable']);
    }

    protected function getTableColumns(): array
    {
        // Gunakan logika polimorfik yang sama seperti di TugasResource
        return [
            TextColumn::make('progressable.identifier')
                ->label('Nomor / Nama Dokumen')
                ->state(function (Progress $record): string {
                    return match (get_class($record->progressable)) {
                        Berkas::class => $record->progressable->nomor_berkas,
                        Perbankan::class => $record->progressable->nama_debitur,
                        TurunWaris::class => $record->progressable->nama_kasus,
                        TandaTerimaSertifikat::class => $record->progressable->penerima,
                        default => 'N/A',
                    };
                }),
            TextColumn::make('progressable.type')
                ->label('Jenis Dokumen')
                ->state(function (Progress $record): string {
                    return match (get_class($record->progressable)) {
                        Berkas::class => 'Berkas Peralihan Hak',
                        Perbankan::class => 'Berkas Perbankan',
                        TurunWaris::class => 'Berkas Turun Waris',
                        TandaTerimaSertifikat::class => 'Tanda Terima Sertifikat',
                        default => 'Tidak Dikenal',
                    };
                }),
            BadgeColumn::make('stage_key')->label('Tahap'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->url(function (Progress $record): string {
                    $parent = $record->progressable;
                    return match (get_class($parent)) {
                        Berkas::class => BerkasResource::getUrl('view', ['record' => $parent]),
                        Perbankan::class => PerbankanResource::getUrl('view', ['record' => $parent]),
                        TurunWaris::class => TurunWarisResource::getUrl('view', ['record' => $parent]),
                        TandaTerimaSertifikat::class => TandaTerimaSertifikatResource::getUrl('view', ['record' => $parent]),
                        default => '#',
                    };
                }),
        ];
    }
}
