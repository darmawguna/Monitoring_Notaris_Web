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
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\BerkasStatus;
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
        if (!in_array($user->role->name, ['Superadmin'])) {
            // Tampilkan hanya berkas Perbankan di mana pengguna ini memiliki tugas 'pending'
            return $query->whereHas('progress', function (Builder $q) use ($user) {
                $q->where('assignee_id', $user->id)->where('status', 'pending');
            });
        }

        if ($user->role->name === 'Petugas Entry') {
            // Tampilkan berkas yang 'selesai' ATAU berkas yang dibuat oleh mereka
            return $query->where(function (Builder $query) use ($user) {
                $query->where('status_overall', BerkasStatus::SELESAI)
                    ->orWhere('created_by', $user->id);
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
        if (in_array($user->role->name, ['Superadmin'])) {
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
                                Select::make('type')->label('Jenis Dokumen')->options(['surat_kematian' => 'Surat Kematian', 'surat_nikah' => 'Surat Nikah', 'ktp_ahli_waris' => 'KTP Ahli Waris', 'kk_ahli_waris' => 'KK Ahli Waris', 'sertifikat' => 'Sertifikat', 'pbb' => 'PBB', 'lainnya' => 'Lainnya'])->required()->reactive(),
                                FileUpload::make('path')->label('Upload File')->disk('public')->directory('turun-waris-attachments')->preserveFilenames()->required(),
                                TextInput::make('type_lainnya')->label('Sebutkan Jenis Dokumen')->placeholder('Contoh: Surat Kuasa')->visible(fn(Get $get): bool => $get('type') === 'lainnya')->required(fn(Get $get): bool => $get('type') === 'lainnya'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Dokumen Lampiran')
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                $standardOptions = ['surat_kematian', 'surat_nikah', 'ktp_ahli_waris', 'kk_ahli_waris', 'sertifikat', 'pbb'];
                                if (!in_array($data['type'], $standardOptions)) {
                                    $data['type_lainnya'] = $data['type'];
                                    $data['type'] = 'lainnya';
                                }
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                if ($data['type'] === 'lainnya' && isset($data['type_lainnya'])) {
                                    $data['type'] = $data['type_lainnya'];
                                }
                                unset($data['type_lainnya']);
                                return $data;
                            }),
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
                        // Gunakan ViewEntry yang menunjuk ke file Blade baru
                        ViewEntry::make('files')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.turun-waris-file-list'),
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