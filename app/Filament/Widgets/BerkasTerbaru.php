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
    protected static ?int $sort = 3; // Urutan di dashboard
    protected int|string|array $columnSpan = 'full'; // Agar widget memenuhi lebar

    public function getTableHeading(): string
    {
        return 'Berkas Terbaru';
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Berkas::query())
            ->defaultSort('created_at', 'desc') // Tampilkan yang terbaru di atas
            ->columns([
                TextColumn::make('nomor')->label('Nomor Berkas'),
                TextColumn::make('nama_berkas'),
                TextColumn::make('pembeli'),
                BadgeColumn::make('current_stage_key')->label('Tahap Saat Ini'),
                TextColumn::make('created_at')->label('Tanggal Dibuat')->date('d M Y'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Berkas $record): string => BerkasResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
