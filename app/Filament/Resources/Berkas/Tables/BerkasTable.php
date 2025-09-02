<?php

namespace App\Filament\Resources\Berkas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BerkasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->searchable(),
                TextColumn::make('nama_berkas')
                    ->searchable(),
                TextColumn::make('penjual')
                    ->searchable(),
                TextColumn::make('pembeli')
                    ->searchable(),
                TextColumn::make('sertifikat_nama')
                    ->searchable(),
                TextColumn::make('total_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status_overall')
                    ->searchable(),
                TextColumn::make('current_stage_key')
                    ->searchable(),
                TextColumn::make('currentAssignee.name')
                    ->searchable(),
                TextColumn::make('deadline_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
