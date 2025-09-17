<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerbankanResource\Pages;
use App\Models\Perbankan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
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

    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        return in_array($userRole, ['Superadmin', 'FrontOffice']);
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
                        Grid::make(2)->schema([
                            TextInput::make('ttl_tempat')->label('Tempat Lahir'),
                            DatePicker::make('ttl_tanggal')->label('Tanggal Lahir'),
                        ]),
                        TextInput::make('npwp')->label('NPWP'),
                        TextInput::make('email')->label('Email')->email(),
                        TextInput::make('telepon')->label('Telepon')->tel(),
                    ])->columns(2),

                Section::make('Informasi Covernote / SKMHT')
                    ->schema([
                        FileUpload::make('berkas_bank')
                            ->label('Upload Berkas Bank')
                            ->disk('public')
                            ->directory('perbankan-attachments')
                            ->preserveFilenames(),
                        // TODO ubah menjadi dropdown
                        Radio::make('jangka_waktu')
                            ->label('Jangka Waktu')
                            ->options([
                                1 => '1 Bulan',
                                3 => '3 Bulan',
                                6 => '6 Bulan',
                            ])
                            ->required(),
                        DatePicker::make('tanggal_covernote')
                            ->label('Tanggal Awal Covernote')
                            ->required()
                            ->default(now()),
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerbankans::route('/'),
            'create' => Pages\CreatePerbankan::route('/create'),
            'edit' => Pages\EditPerbankan::route('/{record}/edit'),
        ];
    }
}