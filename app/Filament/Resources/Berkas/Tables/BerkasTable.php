<?php

namespace App\Filament\Resources\Berkas\Tables;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter; // <-- 1. Impor SelectFilter
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class BerkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')->searchable(),
                TextColumn::make('nama_berkas')->searchable()->limit(25),
                TextColumn::make('pembeli')->searchable(),
                BadgeColumn::make('current_stage_key')
                    ->label('Tahap Saat Ini')
                    ->colors([
                        'primary',
                        'warning' => fn($state) => in_array($state, [StageKey::PETUGAS_2, StageKey::PAJAK, StageKey::PETUGAS_5]),
                        'success' => StageKey::SELESAI,
                    ]),
                TextColumn::make('currentAssignee.name')->label('Ditugaskan Ke')->default('N/A'),
                TextColumn::make('total_cost')->money('IDR')->sortable(),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh')->sortable(),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // --- BAGIAN BARU UNTUK FILTER ---
                SelectFilter::make('current_stage_key')
                    ->label('Tahapan Saat Ini')
                    ->options(StageKey::class), // Otomatis mengambil dari Enum

                SelectFilter::make('status_overall')
                    ->label('Status Keseluruhan')
                    ->options(BerkasStatus::class), // Otomatis mengambil dari Enum

                SelectFilter::make('current_assignee_id')
                    ->label('Petugas')
                    ->relationship('currentAssignee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
