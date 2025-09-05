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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Model;

class BerkasResource extends Resource
{
    protected static ?string $model = Berkas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Section::make('Detail Klien & Penugasan')
                    ->schema([
                        TextInput::make('nama_berkas')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nomor')
                            ->default('B-' . strtoupper(Str::random(8)))
                            ->readOnly()
                            ->required(),
                        TextInput::make('penjual')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('pembeli')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sertifikat_nama'),
                        Textarea::make('persetujuan')
                            ->columnSpanFull(),
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
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Upload Lampiran Berkas')
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
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->required(),
                                FileUpload::make('path')
                                    ->label('Upload File')
                                    ->disk('public')
                                    ->directory('berkas-attachments')
                                    ->preserveFilenames()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Dokumen Lampiran')
                            // ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            //     $data['uploaded_by'] = auth()->id();
                            //     return $data;
                            // }),
                    ]),
                Section::make('Biaya')
                    ->schema([
                        TextInput::make('total_cost')
                            ->label('Total Biaya')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::from('$money($input, \',\')'))
                            ->stripCharacters(',')
                            ->placeholder('2,000,000')
                            ->dehydrateStateUsing(fn(string $state): string => preg_replace('/[^0-9]/', '', $state)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->searchable(),
                TextColumn::make('nama_berkas')
                    ->searchable()
                    ->limit(25),
                TextColumn::make('penjual')
                    ->searchable(),
                BadgeColumn::make('current_stage_key')
                    ->label('Tahap Saat Ini'),
                TextColumn::make('currentAssignee.name')
                    ->label('Ditugaskan Ke')
                    ->default('N/A'),
                TextColumn::make('total_cost')
                    ->money('IDR')
                    ->sortable(),
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
                InfolistSection::make('Detail Berkas & Klien')
                    ->schema([
                        // Ganti semua TextEntry dengan satu ViewEntry
                        ViewEntry::make('berkasDetails')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.berkas-details-section'),
                    ]),

                InfolistSection::make('Status & Finansial')
                    ->schema([
                        // Ganti semua TextEntry dengan satu ViewEntry
                        ViewEntry::make('statusFinancial')
                            ->hiddenLabel()
                            ->view('filament.infolists.sections.status-financial-section'),
                    ]),
                InfolistSection::make('Lampiran Berkas')
                    ->schema([
                        RepeatableEntry::make('files')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Jenis Dokumen'),

                                // --- INI BAGIAN YANG DIPERBARUI ---

                                // Komponen 1: Tampilkan ini HANYA JIKA file adalah gambar
                                ImageEntry::make('path')
                                    ->label('Pratinjau')
                                    ->disk('public')
                                    ->height(80)
                                    // Membuat seluruh gambar bisa diklik untuk memicu Aksi
                                    ->action(
                                        Action::make('previewImage')
                                            ->label('Lihat Ukuran Penuh')
                                            ->modalHeading('Pratinjau Lampiran')
                                            // Ganti ->infolist() dengan ->modalContent() untuk passing data manual
                                            ->modalContent(
                                                fn($record) =>
                                                Infolist::make()
                                                    ->record($record) // <-- Ini adalah bagian yang hilang
                                                    ->schema([
                                                        ImageEntry::make('path')
                                                            ->hiddenLabel()
                                                            ->disk('public')
                                                            ->extraAttributes(['style' => 'display: block; max-width: 100%; height: auto; max-height: 75vh; margin: auto;']),
                                                    ])
                                            )
                                            ->modalSubmitAction(false)
                                            ->modalCancelAction(fn(\Filament\Actions\StaticAction $action) => $action->label('Tutup'))
                                    )
                                    // Logika visibilitas: hanya tampil jika file adalah gambar
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

                            ])->columns(2),
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
