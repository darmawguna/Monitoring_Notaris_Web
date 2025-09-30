<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurunWarisResource\Pages;
use App\Models\TurunWaris;
use App\Models\User;
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
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TurunWarisResource extends Resource
{
    protected static ?string $model = TurunWaris::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Berkas Diluar Peralihan Hak';
    protected static ?string $pluralModelLabel = 'Berkas Diluar Peralihan Hak';
    protected static ?string $navigationLabel = 'Berkas Diluar Peralihan Hak';
    protected static ?string $navigationGroup = 'Berkas'; // Grupkan bersama "Berkas Jual Beli"

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
                            ->relationship()
                            ->label('Lampiran Berkas')
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
                Section::make('Penugasan Awal')
                    ->schema([
                        Select::make('petugas_2_id')
                            ->label('Tugaskan ke Petugas 2')
                            ->options(
                                User::whereHas(
                                    'role',
                                    fn($query) => $query->where('name', 'Petugas2')
                                )->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Kasus')
                    ->schema([
                        TextEntry::make('nama_kasus')->label('Nama Kasus / Klien'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d F Y'),
                    ])
                    ->columns(2),

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
            'index' => Pages\ListTurunWaris::route('/'),
            'create' => Pages\CreateTurunWaris::route('/create'),
            'view' => Pages\ViewTurunWaris::route('/{record}'),
            'edit' => Pages\EditTurunWaris::route('/{record}/edit'),
        ];
    }
}