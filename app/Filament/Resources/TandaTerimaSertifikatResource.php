<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TandaTerimaSertifikatResource\Pages;
use App\Models\TandaTerimaSertifikat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TandaTerimaSertifikatResource extends Resource
{
    protected static ?string $model = TandaTerimaSertifikat::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $modelLabel = 'Tanda Terima Sertifikat';
    protected static ?string $pluralModelLabel = 'Tanda Terima Sertifikat';
    protected static ?string $navigationLabel = 'Tanda Terima Sertifikat';
    protected static ?string $navigationGroup = 'Berkas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Tanda Terima')
                    ->schema([
                        TextInput::make('penyerah')
                            ->label('Yang Menyerahkan Sertifikat (Sertifikat Awal)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('penerima')
                            ->label('Yang Menerima Sertifikat')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('tanggal_terima')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        TextInput::make('sertifikat_info')
                            ->label('Sertifikat Hak Milik (Info/Nomor)')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                Section::make('Dokumen Akhir')
                    ->schema([
                        FileUpload::make('dokumen_akhir_path')
                            ->label('Upload Dokumen File Akhir')
                            ->disk('public')
                            ->directory('tanda-terima-attachments')
                            ->preserveFilenames(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('penyerah')
                    ->label('Yang Menyerahkan')
                    ->searchable(),
                TextColumn::make('penerima')
                    ->label('Yang Menerima')
                    ->searchable(),
                TextColumn::make('sertifikat_info')
                    ->label('Info Sertifikat')
                    ->searchable(),
                TextColumn::make('tanggal_terima')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTandaTerimaSertifikats::route('/'),
            'create' => Pages\CreateTandaTerimaSertifikat::route('/create'),
            'edit' => Pages\EditTandaTerimaSertifikat::route('/{record}/edit'),
        ];
    }
}
