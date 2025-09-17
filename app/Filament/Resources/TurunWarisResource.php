<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurunWarisResource\Pages;
use App\Models\TurunWaris;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TurunWarisResource extends Resource
{
    protected static ?string $model = TurunWaris::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Berkas Turun Waris';
    protected static ?string $pluralModelLabel = 'Berkas Turun Waris';
    protected static ?string $navigationLabel = 'Berkas Turun Waris';
    protected static ?string $navigationGroup = 'Berkas'; // Grupkan bersama "Berkas Jual Beli"

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kasus')
                    ->schema([
                        TextInput::make('nama_kasus')
                            ->label('Nama Kasus / Klien')
                            ->required()
                            ->maxLength(255),
                    ]),
                Section::make('Upload Dokumen')
                    ->schema([
                        Repeater::make('files')
                            // Menghubungkan repeater ini ke relasi 'files' di model TurunWaris
                            ->relationship()
                            ->label('Dokumen Lampiran')
                            ->schema([
                                Select::make('type')
                                    ->label('Jenis Dokumen')
                                    ->options([
                                        'surat_kematian' => 'Surat Kematian',
                                        'surat_nikah' => 'Surat Nikah',
                                        'ktp_ahli_waris' => 'KTP Ahli Waris',
                                        'kk_ahli_waris' => 'KK Ahli Waris',
                                        'sertifikat' => 'Sertifikat',
                                        'pbb' => 'PBB',
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->reactive(),
                                FileUpload::make('path')
                                    ->label('Upload File')
                                    ->disk('public')
                                    ->directory('turun-waris-attachments')
                                    ->preserveFilenames()
                                    ->required(),
                                TextInput::make('type_lainnya')
                                    ->label('Sebutkan Jenis Dokumen')
                                    ->visible(fn(Get $get): bool => $get('type') === 'lainnya')
                                    ->required(fn(Get $get): bool => $get('type') === 'lainnya'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Dokumen')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                if ($data['type'] === 'lainnya' && isset($data['type_lainnya'])) {
                                    $data['type'] = $data['type_lainnya'];
                                }
                                unset($data['type_lainnya']);
                                return $data;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kasus')
                    ->label('Nama Kasus')
                    ->searchable(),
                // Tampilkan jumlah file yang terlampir
                TextColumn::make('files_count')
                    ->counts('files')
                    ->label('Jumlah Dokumen'),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTurunWaris::route('/'),
            'create' => Pages\CreateTurunWaris::route('/create'),
            'edit' => Pages\EditTurunWaris::route('/{record}/edit'),
        ];
    }
}