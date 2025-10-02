<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BerkasResource\Pages;
use App\Filament\Resources\BerkasResource\RelationManagers;
use App\Models\Berkas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\StageKey;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Support\RawJs;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Components\Actions; // Namespace untuk ActionsColumn
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\ViewAction;

class BerkasResource extends Resource
{
    protected static ?string $model = Berkas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Berkas Peralihan Hak';
    protected static ?string $navigationGroup = 'Berkas';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        // Jika pengguna BUKAN Superadmin atau FrontOffice, filter daftar berkasnya.
        if (!in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
            // Tampilkan hanya berkas di mana pengguna ini memiliki tugas yang 'pending'
            return $query->whereHas('progress', function (Builder $q) use ($user) {
                $q->where('assignee_id', $user->id)->where('status', 'pending');
            });
        }

        // Untuk Superadmin dan FrontOffice, tampilkan semuanya dan muat relasi yang dibutuhkan.
        return $query->with(['createdBy']);
    }

    // --- DITAMBAHKAN: OTORISASI PER RECORD ---
    /**
     * Tentukan apakah pengguna bisa melihat record detail.
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        // Superadmin & FrontOffice selalu bisa melihat detail apapun.
        if (in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
            return true;
        }

        // Petugas hanya bisa melihat jika mereka memiliki tugas 'pending' di berkas ini.
        // Ini adalah "penjaga pintu" yang memeriksa "tiket" dari TugasResource.
        return $record->progress()
            ->where('assignee_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    /**
     * The column to use as the main title in global search results.
     */
    protected static ?string $recordTitleAttribute = 'nama_berkas';

    /**
     * The columns that should be searched globally.
     */
    protected static array $globallySearchableAttributes = [
        'nomor',
        'nama_berkas',
        'penjual',
        'pembeli',
    ];


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION BERKAS
                Section::make('Informasi Berkas')
                    ->schema([
                        TextInput::make('nomor_berkas')
                            ->label('Nomor Berkas')
                            // Hapus ->default(...)
                            ->placeholder('Akan digenerate otomatis setelah disimpan')
                            ->readOnly(),
                        // ->required(),
                        Select::make('nama_berkas')
                            ->label('Jenis Berkas')
                            ->options([
                                'jual_beli' => 'Jual Beli',
                                'hibah' => 'Hibah',
                                'tukar_menukar' => 'Tukar Menukar',
                                'aphb' => 'APHB',
                            ])
                            ->required()
                            ->reactive(), // Penting untuk form dinamis
                        // Setelah user memilih, otomatis isi nama pemohon
                        // ->afterStateUpdated(fn(Set $set, ?string $state) => $set('nama_pemohon', null)),
                        TextInput::make('nama_pemohon')
                            ->required()
                            ->maxLength(255),

                    ])->columns(3),

                // SECTION BERKAS JUAL BELI
                Section::make(function (Get $get): string {
                    $jenisBerkas = $get('nama_berkas');
                    return match ($jenisBerkas) {
                        'hibah' => 'Data Pihak Hibah',
                        'tukar_menukar' => 'Data Pihak Tukar Menukar',
                        'aphb' => 'Data Pihak APHB',
                        default => 'Data Pihak Jual Beli',
                    };
                })
                    ->schema([
                        Grid::make(3)->schema([
                            // PIHAK PERTAMA (LABEL DINAMIS)
                            Section::make(function (Get $get): string {
                                $jenisBerkas = $get('nama_berkas');
                                return match ($jenisBerkas) {
                                    'hibah' => 'Data Pemberi Hibah',
                                    'tukar_menukar' => 'Data Pihak A',
                                    'aphb' => 'Data Pihak A',
                                    default => 'Data Penjual',
                                };
                            })
                                ->schema([
                                    TextInput::make('penjual_data.nama')->label('Nama'),
                                    TextInput::make('penjual_data.nik')->label('Identitas / NIK'),
                                    TextInput::make('penjual_data.telp')->label('No. Telp'),
                                    Textarea::make('penjual_data.alamat')->label('Alamat'),
                                ]),

                            // PIHAK KEDUA (LABEL DINAMIS)
                            Section::make(function (Get $get): string {
                                $jenisBerkas = $get('nama_berkas');
                                return match ($jenisBerkas) {
                                    'hibah' => 'Data Penerima Hibah',
                                    'tukar_menukar' => 'Data Pihak B',
                                    'aphb' => 'Data Pihak B',
                                    default => 'Data Pembeli',
                                };
                            })
                                ->schema([
                                    TextInput::make('pembeli_data.nama')->label('Nama'),
                                    TextInput::make('pembeli_data.nik')->label('Identitas / NIK'),
                                    TextInput::make('pembeli_data.telp')->label('No. Telp'),
                                    Textarea::make('pembeli_data.alamat')->label('Alamat'),
                                ]),

                            Section::make('Data Pihak Persetujuan')
                                ->schema([
                                    TextInput::make('pihak_persetujuan_data.nama')->label('Nama'),
                                    TextInput::make('pihak_persetujuan_data.nik')->label('Identitas / NIK'),
                                    TextInput::make('pihak_persetujuan_data.telp')->label('No. Telp'),
                                    Textarea::make('pihak_persetujuan_data.alamat')->label('Alamat'),
                                ]),
                        ])
                    ]),


                // SECTION SERTIFIKAT
                Section::make('Informasi Sertifikat')
                    ->schema([
                        TextInput::make('sertifikat_nomor')->label('Nomor Sertifikat'),
                        TextInput::make('sertifikat_luas')->label('Luas (m²)')->suffix('m²'),
                        Radio::make('sertifikat_jenis')
                            ->label('Jenis Sertifikat')
                            ->options([
                                'elektronik' => 'Elektronik',
                                'analog' => 'Analog',
                            ]),
                        Select::make('sertifikat_tipe')
                            ->label('Tipe Sertifikat')
                            ->options([
                                'hm' => 'Hak Milik (HM)',
                                'hgb' => 'Hak Guna Bangunan (HGB)',
                                'hp' => 'Hak Pakai (HP)',
                                'hgu' => 'Hak Guna Usaha'
                            ])
                            ->searchable(),
                        TextInput::make('nilai_transaksi')
                            ->label('Nilai Transaksi')
                            ->prefix('Rp')
                            ->mask(RawJs::from('$money($input, \',\')'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn($state): ?string => $state ? preg_replace('/[^0-9]/', '', $state) : null)
                            ->helperText('Masukkan total estimasi biaya awal.'),
                    ])->columns(2),

                // SECTION PBB
                Section::make('Informasi Akta')
                    ->schema([
                        TextInput::make('pbb_validasi')->label('Validasi PBB'),
                        TextInput::make('pbb_akta_bpjb')->label('Akta PPJB'),
                        TextInput::make('pbb_nop')->label('NOP'),
                    ])->columns(3),



                Section::make('Upload Dokumen')
                    ->schema([
                        Repeater::make('files')
                            ->relationship()
                            ->label('Lampiran Berkas')
                            ->schema([
                                Select::make('type')
                                    ->label('Jenis Dokumen')
                                    ->options([
                                        'ktp_suami' => 'KTP Suami',
                                        'ktp_istri' => 'KTP Istri',
                                        'kk' => 'Kartu Keluarga',
                                        'sertifikat' => 'Sertifikat',
                                        'pbb' => 'PBB',
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->required()
                                    // 1. Buat dropdown ini reaktif
                                    ->reactive(),

                                FileUpload::make('path')
                                    ->label('Upload File')
                                    ->disk('public')
                                    ->directory('berkas-attachments')
                                    ->preserveFilenames()
                                    ->required(),

                                // 2. Tambahkan TextInput kondisional
                                TextInput::make('type_lainnya')
                                    ->label('Sebutkan Jenis Dokumen')
                                    ->placeholder('Contoh: Surat Kuasa')
                                    // 3. Atur visibilitas dan persyaratan
                                    ->visible(fn(Get $get): bool => $get('type') === 'lainnya')
                                    ->required(fn(Get $get): bool => $get('type') === 'lainnya'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Dokumen Lampiran')
                            // 4. Perbarui logika penyimpanan data
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                // Jika pengguna memilih 'lainnya', gunakan input teks sebagai gantinya.
                                if ($data['type'] === 'lainnya' && isset($data['type_lainnya'])) {
                                    $data['type'] = $data['type_lainnya'];
                                }
                                // Hapus data sementara yang tidak perlu disimpan
                                unset($data['type_lainnya']);
                                return $data;
                            }),
                    ]),


                // Kolom penugasan (tetap ada di bawah agar alur kerja tidak berubah)
                Section::make('Penugasan Awal')
                    ->schema([
                        Select::make('petugas_2_id') // 1. Nama field sudah benar
                            ->label('Tugaskan ke Petugas 2')
                            ->options( // 2. Menggunakan ->options() sudah benar
                                User::whereHas(
                                    'role',
                                    fn($query) => $query->where('name', 'Petugas2')
                                )->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                        // 3. HAPUS ->mapped(false) karena sudah ditangani di CreateBerkas.php
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Ganti 'nomor' menjadi 'nomor_berkas'
                TextColumn::make('nomor_berkas')
                    ->label('Nomor Berkas')
                    // Logika pencarian terpusat di sini
                    ->searchable(
                        [
                            'nomor_berkas',
                            'nama_berkas',
                            'nama_pemohon',
                            'penjual_data->nama', // Cara mencari di dalam JSON
                            'pembeli_data->nama', // Cara mencari di dalam JSON
                        ],
                        isIndividual: false
                    ),

                TextColumn::make('nama_berkas')
                    ->limit(25),

                TextColumn::make('nama_pemohon'),

                // 2. Tampilkan nama dari dalam kolom JSON 'penjual_data'
                TextColumn::make('penjual_data.nama')
                    ->label('Penjual'),

                // 3. Tampilkan nama dari dalam kolom JSON 'pembeli_data'
                TextColumn::make('pembeli_data.nama')
                    ->label('Pembeli'),

                BadgeColumn::make('current_stage_key')
                    ->label('Tahap Saat Ini'),

                TextColumn::make('current_assignee_name')
                    ->label('Ditugaskan Ke')
                    ->state(function (Berkas $record): string {
                        // Cari progres terakhir yang masih 'pending'
                        $latestPendingProgress = $record->progress()->where('status', 'pending')->latest()->first();
                        if ($record->status_overall->value === 'selesai') {
                            return 'Selesai';
                        }
                        // Tampilkan nama petugas dari progres tersebut
                        return $latestPendingProgress?->assignee?->name ?? 'Belum ditugaskan';
                    }),

                // --- AKHIR DARI PERUBAHAN ---
            ])
            ->actions([
                ViewAction::make()->url(fn($record) => self::getUrl('view', ['record' => $record])),
                // ViewAction::make()->url(fn(Berkas $record) => BerkasResource::getUrl('view', ['record' => $record])),
            ])

            ->filters([
                SelectFilter::make('current_stage_key')
                    ->label('Tahapan')
                    ->options(StageKey::class),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Berkas')
                    ->schema([
                        ViewEntry::make('berkasInfo')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.berkas-info-section'),
                    ]),

                InfolistSection::make('Data Pihak Jual Beli')
                    ->schema([
                        ViewEntry::make('jualBeliInfo')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.jual-beli-section'),
                    ]),

                InfolistSection::make('Informasi Sertifikat')
                    ->schema([
                        ViewEntry::make('sertifikatInfo')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.sertifikat-section'),
                    ]),

                InfolistSection::make('Informasi PBB')
                    ->schema([
                        ViewEntry::make('pbbInfo')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.pbb-section'),
                    ]),


                InfolistSection::make('Lampiran Berkas')
                    ->schema([
                        RepeatableEntry::make('files')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Jenis Dokumen'),

                                // --- INI BAGIAN YANG DIPERBARUI SECARA TOTAL ---

                                // Komponen 1: Tampilkan thumbnail gambar (tidak bisa diklik)
                                ImageEntry::make('path')
                                    ->label('Pratinjau')
                                    ->disk('public')
                                    ->height(80)
                                    ->visible(function ($record): bool {
                                        if (!$record || !$record->path)
                                            return false;
                                        return Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg'], strtolower($record->path));
                                    }),

                                // Komponen 2: Fallback untuk file yang BUKAN gambar
                                TextEntry::make('path')
                                    ->label('File')
                                    ->formatStateUsing(fn(?string $state): string => $state ? basename($state) : 'N/A')
                                    ->url(fn($record) => $record->path ? Storage::url($record->path) : null, true)
                                    ->color('primary')
                                    ->visible(function ($record): bool {
                                        if (!$record || !$record->path)
                                            return false;
                                        return !Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg'], strtolower($record->path));
                                    }),

                                // Komponen 3: Kolom Aksi Terpisah
                                // TODO perbarui alignmentnya agar berada pada posisi yang sesuai.
                                Actions::make([
                                    // Aksi untuk membuka modal pratinjau
                                    Action::make('preview')
                                        ->label('Pratinjau')
                                        ->icon('heroicon-o-eye')
                                        ->modalContent(
                                            fn($record) =>
                                            Infolist::make()
                                                ->record($record)
                                                ->schema([
                                                    ImageEntry::make('path')->hiddenLabel()->disk('public')->extraAttributes(['style' => 'display: block; max-width: 100%; height: auto; margin: auto;']),
                                                ])
                                        )
                                        ->modalSubmitAction(false)
                                        ->modalCancelAction(false) // Sembunyikan tombol default
                                        ->visible(function ($record): bool {
                                            if (!$record || !$record->path)
                                                return false;
                                            return Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg'], strtolower($record->path));
                                        }),

                                    // Aksi untuk mengunduh file
                                    Action::make('download')
                                        ->label('Unduh')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('success')
                                        ->url(fn($record) => route('files.download', ['appFile' => $record]), shouldOpenInNewTab: true)
                                ])->label('Aksi')
                                    ->alignment(Alignment::Center),
                            ])->columns(3), // Ubah menjadi 3 kolom
                    ])->collapsible(),

                InfolistSection::make('Riwayat & Durasi Pengerjaan')
                    ->schema([
                        // Ganti semua RepeatableEntry dengan satu ViewEntry
                        ViewEntry::make('progressHistory')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.progress-history-section'),
                    ])->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBerkas::route('/'),
            'create' => Pages\CreateBerkas::route('/create'),
            'view' => Pages\ViewBerkas::route('/{record}'),
            'edit' => Pages\EditBerkas::route('/{record}/edit'),
        ];
    }
}
