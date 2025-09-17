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
// use Filament\Infolists\Components\Actions\Action;
// use Filament\Infolists\Components\ImageEntry;

class BerkasResource extends Resource
{
    protected static ?string $model = Berkas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Berkas Jual Beli';
    protected static ?string $navigationGroup = 'Berkas';

    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        return in_array($userRole, ['Superadmin', 'FrontOffice']);
    }

    public static function getEloquentQuery(): Builder
    {
        // Secara otomatis memuat relasi 'currentAssignee' dan 'createdBy'
        // untuk mencegah N+1 query di tabel dan infolist.
        return parent::getEloquentQuery()->with(['currentAssignee', 'createdBy']);
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

    public static function canEdit(Model $record): bool
    {
        // Hanya izinkan edit jika peran pengguna adalah Superadmin.
        return auth()->user()->role->name === 'Superadmin';
    }

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
                        TextInput::make('nama_berkas')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nama_pemohon')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),

                // SECTION BERKAS JUAL BELI
                Section::make('Data Pihak Jual Beli')
                    ->schema([
                        Grid::make(3)->schema([
                            Section::make('Data Penjual')
                                ->schema([
                                    TextInput::make('penjual_data.nama')->label('Nama'),
                                    TextInput::make('penjual_data.nik')->label('Identitas / NIK'),
                                    TextInput::make('penjual_data.telp')->label('No. Telp'),
                                    Textarea::make('penjual_data.alamat')->label('Alamat'),
                                ]),
                            Section::make('Data Pembeli')
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
                            ->dehydrateStateUsing(fn($state): ?string => $state ? preg_replace('/[^0-9]/', '', $state) : null),
                    ])->columns(2),

                // SECTION PBB
                Section::make('Informasi PBB')
                    ->schema([
                        TextInput::make('pbb_sppt')->label('SPPT'),
                        TextInput::make('pbb_nop')->label('NOP'),
                        TextInput::make('pbb_validasi')->label('Validasi PBB'),
                        TextInput::make('pbb_akta_bpjb')->label('Akta BPJB'),
                        TextInput::make('pbb_nomor')->label('Nomor PBB'),
                    ])->columns(3),

                // SECTION BANK
                // TODO Perbarui lagi sesuai dengan revisian yang ada
                Section::make('Informasi Bank')
                    ->schema([
                        TextInput::make('bank_kredit')->label('Bank / Kredit Bank'),
                    ]),

                Section::make('Pendaftaran Sertifikat (Upload Dokumen)')
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
                        Select::make('current_assignee_id')
                            ->label('Tugaskan ke Petugas 2')
                            ->relationship(
                                name: 'currentAssignee',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->whereHas(
                                    'role',
                                    fn(Builder $query) => $query->where('name', 'Petugas2')
                                ),
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
                // Kolom penomoran yang sudah kita buat sebelumnya
                // TextColumn::make('No.')
                //     ->rowIndex()
                //     ->formatStateUsing(function (HasTable $livewire, string $state): string {
                //         $currentPage = $livewire->getTable()->getRecords()->currentPage();
                //         $perPage = $livewire->getTable()->getRecords()->perPage();
                //         return (string) (($currentPage - 1) * $perPage + (int) $state + 1);
                //     }),
                // --- INI BAGIAN YANG DIPERBARUI ---

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

                TextColumn::make('currentAssignee.name')
                    ->label('Ditugaskan Ke')
                    ->state(fn($record): string => $record->status_overall->value === 'selesai' ? 'Selesai (Tidak ada)' : $record->currentAssignee?->name ?? 'Belum ditugaskan'),

                // --- AKHIR DARI PERUBAHAN ---
            ])

            ->filters([
                SelectFilter::make('current_stage_key')
                    ->label('Tahapan')
                    ->options(StageKey::class),
                SelectFilter::make('current_assignee_id')
                    ->label('Petugas')
                    ->relationship('currentAssignee', 'name')
                    ->searchable()
                    ->preload(),
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

                InfolistSection::make('Informasi Bank')
                    ->schema([
                        ViewEntry::make('bankInfo')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.bank-section'),
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
                                        ->url(fn($record) => route('berkas-files.download', ['berkasFile' => $record]), shouldOpenInNewTab: true)
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
