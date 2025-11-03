<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TandaTerimaSertifikatResource\Pages;
use App\Models\TandaTerimaSertifikat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TandaTerimaSertifikatResource extends Resource
{
    protected static ?string $model = TandaTerimaSertifikat::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $modelLabel = 'Tanda Terima Sertifikat';
    protected static ?string $pluralModelLabel = 'Tanda Terima Sertifikat';
    protected static ?string $navigationLabel = 'Tanda Terima Sertifikat';
    protected static ?string $navigationGroup = 'Berkas';
    protected static ?string $recordTitleAttribute = 'nomor_berkas';

    /**
     * Izinkan pencarian berdasarkan nomor berkas, penyerah, dan penerima.
     */
    protected static array $globallySearchableAttributes = [
        'nomor_berkas',
        'penyerah',
        'penerima'
    ];
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Penyerah' => $record->penyerah,
            'Penerima' => $record->penerima,
        ];
    }
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        $userRole = $user->role->name;

        // 1. Superadmin dan Front Office selalu bisa melihat.
        if (in_array($userRole, ['Superadmin', 'Petugas Entry'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Tanda Terima')
                    ->schema([
                        TextInput::make('nomor_berkas')
                            ->label('Nomor Berkas')
                            ->placeholder('Akan digenerate otomatis setelah disimpan')
                            ->readOnly()
                            ->columnSpanFull(),
                        TextInput::make('penyerah')
                            ->label('Yang Menyerahkan Sertifikat (Sertifikat Awal)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('penerima')
                            ->label('Yang Menerima Sertifikat')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('tanggal_terima')
                            ->label('Tanggal Terima')
                            ->required()
                            ->default(now()),
                        DatePicker::make('tanggal_menyerahkan')
                            ->label('Tanggal Menyerahkan')
                            ->required()
                            ->default(now()),
                        TextInput::make('sertifikat_info')
                            ->label('Sertifikat Hak Milik (Info/Nomor)')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                Section::make('Detail Informasi')
                    ->schema([
                        TextInput::make('informasi_tambahan')
                            ->label('Detail informasi surat')
                            ->required()
                            ->helperText("masukan informasi terkait sertifikat yang diserahkan")
                            ->maxLength(length: 1000), // ⚠️ Catatan penting di bawah!
                    ]),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Tanda Terima')
                    ->schema([
                        TextEntry::make('nomor_berkas'),
                        TextEntry::make('penyerah'),
                        TextEntry::make('penerima'),
                        TextEntry::make('tanggal_terima')->date('d F Y'),
                        TextEntry::make('tanggal_menyerahkan')->date('d F Y'),
                        TextEntry::make('sertifikat_info')->columnSpanFull(),
                        TextEntry::make('informasi_tambahan')->columnSpanFull(),
                    ])->columns(2),
                InfolistSection::make('Dokumen Akhir')
                    ->schema([
                        \Filament\Infolists\Components\Actions::make([
                            InfolistAction::make('download_file')
                                ->label('Download Lampiran')
                                ->icon('heroicon-o-paper-clip')
                                ->color('gray')
                                ->url(fn(TandaTerimaSertifikat $record) => route('tanda-terima.file.download', ['record' => $record]), true)
                                ->visible(fn(TandaTerimaSertifikat $record): bool => !empty($record->dokumen_akhir_path)),
                        ])->hiddenLabel()
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_berkas')
                    ->label('Nomor Berkas')
                    ->searchable()
                    ->sortable(),
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
            ])
            ->actions([
                TableAction::make('download')
                    ->label('Download Dokumen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(TandaTerimaSertifikat $record) => route('tanda-terima.download', ['record' => $record]), shouldOpenInNewTab: true)
                    ->tooltip('Download dokumen Word tanda terima')
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTandaTerimaSertifikats::route('/'),
            'create' => Pages\CreateTandaTerimaSertifikat::route('/create'),
            'view' => Pages\ViewTandaTerimaSertifikat::route('/{record}'),
            'edit' => Pages\EditTandaTerimaSertifikat::route('/{record}/edit'),
        ];
    }
}
