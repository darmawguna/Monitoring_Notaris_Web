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
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\DB;
use Filament\Infolists\Components\Actions\Action;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Enums\BerkasStatus;
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

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        $userRole = $user->role->name;

        // 1. Cek Superadmin terlebih dahulu
        if ($userRole === 'Superadmin') {
            return $query; // Tampilkan semua
        }

        // 2. Cek Petugas Entry (FrontOffice)
        if ($userRole === 'Petugas Entry') {
            return $query->where(function (Builder $query) use ($user) {
                $query->where('status_overall', BerkasStatus::SELESAI)
                    ->orWhere('created_by', $user->id);
            });
        }

        // 3. Jika bukan keduanya, berarti ini adalah Petugas lain
        // Tampilkan hanya berkas Perbankan di mana pengguna ini memiliki tugas 'pending'
        return $query->whereHas('progress', function (Builder $q) use ($user) {
            $q->where('assignee_id', $user->id)->where('status', 'pending');
        });
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        $userRole = $user->role->name;

        // 1. Superadmin bisa melihat semuanya
        if ($userRole === 'Superadmin') {
            return true;
        }

        // 2. Petugas Entry bisa melihat jika Selesai ATAU mereka yang buat
        if ($userRole === 'Petugas Entry') {
            return $record->status_overall === BerkasStatus::SELESAI || $record->created_by === $user->id;
        }

        // 3. Petugas lain hanya bisa melihat jika punya tugas pending
        return $record->progress()
            ->where('assignee_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        $userRole = $user->role->name;

        // Aturan 1: Superadmin dan Petugas Entry selalu bisa mengedit.
        if (in_array($userRole, ['Superadmin', 'Petugas Entry'])) {
            return true;
        }

        // Aturan 2: Petugas lain bisa mengedit HANYA
        // jika mereka memiliki tugas 'pending' untuk berkas ini.
        return $record->progress()
            ->where('assignee_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }


    public static function form(Form $form): Form
    {
        $isReadOnlyForPetugas = fn(string $operation): bool =>
            $operation === 'edit' && !in_array(auth()->user()->role->name, ['Superadmin', 'Petugas Entry']);
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
                    ])->columns(2)
                    ->disabled($isReadOnlyForPetugas),

                // --- SECTION BARU UNTUK KREDITUR ---
                Section::make('Informasi Kreditur')
                    ->schema([
                        TextInput::make('nama_kreditur')->label('Nama Kreditur'),
                        TextInput::make('nomor_pk')->label('Nomor PK (Perjanjian Kredit)'),
                    ])->columns(2)
                    ->disabled($isReadOnlyForPetugas),
                // --- AKHIR DARI SECTION BARU ---

                Section::make('Informasi Covernote / SKMHT')
                    ->schema([
                        Repeater::make('files') // Nama harus cocok dengan relasi di Model
                            ->label('Upload Berkas Bank')
                            ->relationship() // Ini akan secara otomatis terhubung ke PerbankanFile
                            ->schema([
                                FileUpload::make('path') // Nama kolom di tabel perbankan_files
                                    ->label('File')
                                    ->disk('public')
                                    ->directory('perbankan-attachments')
                                    ->preserveFilenames()
                                    ->required(),
                            ])
                            ->maxItems(1) // Tetap batasi hanya satu file
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
                            ->reactive()
                            ->disabled($isReadOnlyForPetugas),

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
                            ->default(now())
                            ->disabled($isReadOnlyForPetugas),
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
                            ->required()
                            ->disabled($isReadOnlyForPetugas),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // --- PERBAIKAN: Hapus getTableQuery(), modifikasi query di sini ---
            ->columns([
                TextColumn::make('nama_debitur')
                    ->label('Nama Debitur')
                    ->searchable(),

                TextColumn::make('nama_kreditur')
                    ->label('Nama Kreditur'),

                // --- PERBAIKAN: Kolom Tanggal Berakhir sekarang menjadi Tanggal Deadline ---
                TextColumn::make('tanggal_covernote')
                    ->label('Tanggal Deadline')
                    ->date('d M Y')
                    ->formatStateUsing(function ($record): string {
                        if (!$record->tanggal_covernote || !$record->jangka_waktu) {
                            return '-';
                        }
                        return Carbon::parse($record->tanggal_covernote)
                            ->addMonths($record->jangka_waktu)
                            ->translatedFormat('d F Y');
                    }),

                // --- PERBAIKAN: Tambahkan Kolom Sisa Hari dengan Logika yang Benar ---
                TextColumn::make('sisa_hari')
                    ->label('Sisa Hari')
                    ->state(function (Perbankan $record): ?int {
                        if (!$record->tanggal_covernote || !$record->jangka_waktu) {
                            return null;
                        }
                        $deadline = Carbon::parse($record->tanggal_covernote)->addMonths($record->jangka_waktu);
                        return Carbon::now()->diffInDays($deadline, false);
                    })
                    ->formatStateUsing(function (?int $state): string {
                        if (is_null($state))
                            return '-';
                        if ($state < 0)
                            return 'Terlewat ' . ($state * -1) . ' hari';
                        if ($state === 0)
                            return 'Hari Ini';
                        return $state . ' hari lagi';
                    })
                    ->color(fn(?int $state): string => ($state !== null && $state <= 7) ? 'danger' : 'primary'),

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
                        // Komponen untuk tombol Aksi (dengan logika yang diperbaiki)
                        // ViewEntry::make('files')
                        //     ->hiddenLabel() // Kita tidak perlu label di atasnya
                        //     ->view('filament.infolists.components.perbankan-file-entry'),
                    ]),
                InfolistSection::make('Dokumen Perbankan')
                    ->schema([
                        \Filament\Infolists\Components\Actions::make([
                            InfolistAction::make('download_file')
                                ->label('Download Lampiran')
                                ->icon('heroicon-o-paper-clip')
                                ->color('gray')
                                ->url(function (Perbankan $record): string {
                                    // 1. Ambil file pertama dari relasi 'files'
                                    $file = $record->files->first();
                                    // 2. Jika tidak ada file, kembalikan URL yang aman
                                    if (!$file) {
                                        return '#';
                                    }
                                    return route('perbankan-files.download', ['perbankanFile' => $file]);
                                }, shouldOpenInNewTab: true)
                                // 4. Perbarui logika visibilitas
                                ->visible(fn(Perbankan $record): bool => $record->files->isNotEmpty()),
                        ])->hiddenLabel() // Sembunyikan label "Actions"
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