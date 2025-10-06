<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerbankanResource\Pages;
use App\Models\Perbankan;
use App\Models\user;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Grid as FormGrid;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Actions\Action;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
class PerbankanResource extends Resource
{
    protected static ?string $model = Perbankan::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $modelLabel = 'Perbankan';
    protected static ?string $pluralModelLabel = 'Perbankan';
    protected static ?string $slug = 'perbankan';
    protected static ?string $navigationLabel = 'Berkas Perbankan';
    protected static ?string $navigationGroup = 'Berkas';
    // protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'nama_debitur';

    /**
     * Izinkan pencarian berdasarkan nama debitur, nomor PK, dan nama kreditur.
     */
    protected static array $globallySearchableAttributes = [
        'nama_debitur',
        'nomor_pk',
        'nama_kreditur'
    ];

    /**
     * Tampilkan detail tambahan di hasil pencarian.
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Kreditur' => $record->nama_kreditur,
            'Nomor PK' => $record->nomor_pk,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Jika pengguna BUKAN Superadmin atau FrontOffice, filter daftar berkasnya.
        if (!in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
            // Tampilkan hanya berkas Perbankan di mana pengguna ini memiliki tugas 'pending'
            return $query->whereHas('progress', function (Builder $q) use ($user) {
                $q->where('assignee_id', $user->id)->where('status', 'pending');
            });
        }

        // Untuk admin, tampilkan semuanya.
        return $query;
    }

    // --- PERBAIKAN 2: Otorisasi Per Record ---
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        // Superadmin & FrontOffice selalu bisa melihat detail apapun.
        if (in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
            return true;
        }

        // Petugas hanya bisa melihat jika mereka memiliki tugas 'pending' di record ini.
        return $record->progress()
            ->where('assignee_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Debitur')
                    ->schema([
                        Select::make('tipe_pemohon')
                            ->label('Tipe Pemohon')
                            ->options([
                                'perorangan' => 'Perorangan',
                                'badan_usaha' => 'Badan Usaha',
                            ]),
                        TextInput::make('nik')->label('NIK'),
                        TextInput::make('nama_debitur')->label('Nama Debitur')->required(),
                        Textarea::make('alamat_debitur')->label('Alamat')->columnSpanFull(),
                        FormGrid::make(2)->schema([
                            TextInput::make('ttl_tempat')->label('Tempat Lahir'),
                            DatePicker::make('ttl_tanggal')->label('Tanggal Lahir'),
                        ]),
                        TextInput::make('npwp')->label('NPWP'),
                        TextInput::make('email')->label('Email')->email(),
                        TextInput::make('telepon')->label('Telepon')->tel(),
                    ])->columns(2),

                // --- SECTION BARU UNTUK KREDITUR ---
                Section::make('Informasi Kreditur')
                    ->schema([
                        TextInput::make('nama_kreditur')->label('Nama Kreditur'),
                        TextInput::make('nomor_pk')->label('Nomor PK (Perjanjian Kredit)'),
                    ])->columns(2),
                // --- AKHIR DARI SECTION BARU ---

                Section::make('Informasi Covernote / SKMHT')
                    ->schema([
                        Repeater::make('files') // Nama harus cocok dengan relasi di Model
                            ->label('Upload Berkas Bank')
                            ->relationship() // Ini adalah kuncinya!
                            ->schema([
                                FileUpload::make('path') // Nama harus cocok dengan kolom di tabel 'app_files'
                                    ->label('File')
                                    ->disk('public')
                                    ->directory('perbankan-attachments')
                                    ->preserveFilenames()
                                    ->required(),
                            ])
                            ->maxItems(1) // Batasi agar hanya bisa upload 1 file
                            ->addActionLabel('Tambah Berkas Bank'),

                        Select::make('jangka_waktu')
                            ->label('Jangka Waktu')
                            ->default(1)
                            ->options([
                                1 => '1 Bulan',
                                3 => '3 Bulan',
                                6 => '6 Bulan',
                                0 => 'Lainnya (dalam bulan)',
                            ])
                            ->required()
                            ->reactive(), // Buat dropdown ini reaktif

                        // Input teks tambahan untuk "Lainnya"
                        TextInput::make('jangka_waktu_lainnya')
                            ->label('Sebutkan Jangka Waktu (Bulan)')
                            ->numeric()
                            ->suffix('Bulan')
                            // Hanya muncul jika 'jangka_waktu' adalah 0
                            ->visible(fn(Get $get): bool => $get('jangka_waktu') == 0)
                            ->required(fn(Get $get): bool => $get('jangka_waktu') == 0),
                        DatePicker::make('tanggal_covernote')
                            ->label('Tanggal Awal Covernote')
                            ->required()
                            ->default(now()),
                    ]),
                Section::make('Penugasan Awal')
                    ->schema([
                        Select::make('petugas_pengetikan_id')
                            ->label('Tugaskan ke Petugas Pengetikan')
                            ->options(
                                User::whereHas(
                                    'role',
                                    fn($query) => $query->where('name', 'Petugas Pengetikan')
                                )->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_debitur')
                    ->label('Nama Debitur')
                    ->searchable(),
                TextColumn::make('tipe_pemohon')
                    ->label('Tipe'),
                TextColumn::make('jangka_waktu')
                    ->label('Jangka Waktu')
                    ->formatStateUsing(fn(string $state): string => "{$state} Bulan"),
                TextColumn::make('tanggal_covernote')
                    ->label('Tanggal Berakhir')
                    ->date('d M Y')
                    ->formatStateUsing(function ($record): string {
                        if (!$record->tanggal_covernote || !$record->jangka_waktu) {
                            return '-';
                        }
                        return Carbon::parse($record->tanggal_covernote)
                            ->addMonths($record->jangka_waktu)
                            ->translatedFormat('d F Y');
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Debitur')
                    ->schema([
                        InfoListGrid::make(2)->schema([
                            TextEntry::make('tipe_pemohon')
                                ->label('Tipe Pemohon')
                                ->formatStateUsing(fn(?string $state): string => match ($state) {
                                    'perorangan' => 'Perorangan',
                                    'badan_usaha' => 'Badan Usaha',
                                    default => $state ?? '-',
                                }),
                            TextEntry::make('nik')->label('NIK'),
                            TextEntry::make('nama_debitur')->label('Nama Debitur'),
                            TextEntry::make('alamat_debitur')->label('Alamat')->columnSpanFull(),
                            TextEntry::make('ttl_tempat')->label('Tempat Lahir'),
                            TextEntry::make('ttl_tanggal')->label('Tanggal Lahir')->date('d F Y'),
                            TextEntry::make('npwp')->label('NPWP'),
                            TextEntry::make('email')->label('Email'),
                            TextEntry::make('telepon')->label('Telepon'),
                        ]),
                    ]),

                InfolistSection::make('Informasi Kreditur')
                    ->schema([
                        InfoListGrid::make(2)->schema([
                            TextEntry::make('nama_kreditur')->label('Nama Kreditur'),
                            TextEntry::make('nomor_pk')->label('Nomor PK (Perjanjian Kredit)'),
                        ]),
                    ]),

                InfolistSection::make('Informasi Covernote / SKMHT')
                    ->schema([
                        TextEntry::make('jangka_waktu')
                            ->label('Jangka Waktu')
                            ->formatStateUsing(function (?int $state, Get $get): string {
                                if ($state === 0) {
                                    $lainnya = $get('jangka_waktu_lainnya');
                                    return $lainnya ? "{$lainnya} Bulan" : '-';
                                }
                                return match ($state) {
                                    1 => '1 Bulan',
                                    3 => '3 Bulan',
                                    6 => '6 Bulan',
                                    default => $state ? "{$state} Bulan" : '-',
                                };
                            }),
                        TextEntry::make('tanggal_covernote')
                            ->label('Tanggal Awal Covernote')
                            ->date('d F Y'),
                        ImageEntry::make('files.0.path') // Mengakses path dari file pertama di relasi
                            ->label('Pratinjau Berkas')
                            ->disk('public')
                            ->height(150)
                            ->visible(function ($record): bool {
                                $file = $record->files->first(); // Dapatkan record file pertama
                                if (!$file) {
                                    return false;
                                }
                                return Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp'], strtolower($file->path));
                            }),
                        // Komponen untuk tombol Aksi (dengan logika yang diperbaiki)
                        \Filament\Infolists\Components\Actions::make([
                            Action::make('download')
                                ->label('Download Berkas Bank')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                // 1. Perbaiki logika URL
                                ->url(function (Perbankan $record) {
                                    // Dapatkan record file pertama dari relasi
                                    $file = $record->files->first();
                                    if ($file) {
                                        // Kirim record AppFile yang benar ke rute
                                        return route('files.download', ['appFile' => $file]);
                                    }
                                    return '#'; // URL fallback jika file tidak ada
                                }, shouldOpenInNewTab: true)
                                // 2. Perbaiki logika visibility
                                ->visible(fn(Perbankan $record): bool => !$record->files->isEmpty()),
                        ])->hiddenLabel(),
                    ]),

                // Opsional: Riwayat & Durasi (jika masih relevan)
                InfolistSection::make('Riwayat & Durasi Pengerjaan')
                    ->schema([
                        ViewEntry::make('progressHistory')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.progress-history-section'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerbankans::route('/'),
            'create' => Pages\CreatePerbankan::route('/create'),
            'view' => Pages\ViewPerbankan::route('/{record}'),
            'edit' => Pages\EditPerbankan::route('/{record}/edit'),
        ];
    }
}