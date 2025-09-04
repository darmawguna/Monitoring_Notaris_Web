<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeadlineConfigResource\Pages;
use App\Models\DeadlineConfig;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeadlineConfigResource extends Resource
{
    protected static ?string $model = DeadlineConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $modelLabel = 'Konfigurasi Deadline';
    protected static ?string $pluralModelLabel = 'Konfigurasi Deadline';
    protected static ?string $navigationLabel = 'Deadline';
    protected static ?string $navigationGroup = 'Pengaturan'; // Grupkan di bawah menu "Pengaturan"
    protected static ?int $navigationSort = 2;

    /**
     * Sembunyikan menu ini dari semua orang kecuali Superadmin.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('stage_key')
                    ->label('Nama Tahapan')
                    ->disabled() // Tidak bisa diubah
                    ->dehydrated(), // Pastikan nilainya tetap disimpan
                TextInput::make('default_days')
                    ->label('Jumlah Hari Default')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->suffix('Hari'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Kita tidak ingin pengguna bisa membuat atau menghapus tahapan
            ->headerActions([])
            ->bulkActions([])
            ->columns([
                TextColumn::make('stage_key')
                    ->label('Nama Tahapan'),
                TextColumn::make('default_days')
                    ->label('Jumlah Hari Default')
                    ->suffix(' hari')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeadlineConfigs::route('/'),
            // Kita tidak memerlukan halaman Create atau View
            'edit' => Pages\EditDeadlineConfig::route('/{record}/edit'),
        ];
    }
}