<?php

namespace App\Filament\Resources;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource\Pages;
use App\Models\Berkas;
use App\Models\Receipt;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KwitansiResource extends Resource
{
    protected static ?string $model = Receipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $modelLabel = 'Kwitansi';
    protected static ?string $pluralModelLabel = 'Audit Kwitansi';
    protected static ?string $navigationLabel = 'Kwitansi';
    protected static ?string $navigationGroup = 'Keuangan';
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        $userRole = $user->role->name;

        // 1. Superadmin dan Front Office selalu bisa melihat.
        if (in_array($userRole, ['Superadmin', 'FrontOffice'])) {
            return true;
        }

        return false;
    }
    // ... (properti lain tetap sama)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kwitansi')
                    ->schema([
                        TextInput::make('receipt_number')
                            ->label('Nomor Kwitansi')
                            ->placeholder('Akan digenerate otomatis')
                            ->readOnly(),
                        TextInput::make('nama_pemohon_kwitansi')
                            ->label('Nama Pemohon')
                            ->required(),
                        // Opsi untuk menautkan ke berkas yang sudah ada
                        Select::make('berkas_id')
                            ->label('Tautkan ke Berkas (Opsional)')
                            ->relationship('berkas', 'nomor_berkas')
                            ->searchable()
                            ->preload(),
                        Textarea::make('notes_kwitansi')
                            ->label('Catatan Kwitansi')
                            ->columnSpanFull()
                            ->helperText("tambahkan catatan terkait kwitansi seperti : lunas/blm lunas"),
                        Textarea::make('informasi_kwitansi')
                            ->label('Informasi peruntukan Kwitansi')
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Rincian Biaya')
                    ->schema([
                        Repeater::make('detail_biaya')
                            ->label('Item Rincian Biaya')
                            ->schema([
                                TextInput::make('deskripsi')->required(),
                                TextInput::make('jumlah')
                                    ->required()
                                    ->prefix('Rp')
                                    ->mask(RawJs::from('$money($input, \'.\')'))
                                    ->stripCharacters('.')
                                    ->dehydrateStateUsing(fn($state): string => preg_replace('/[^0-9]/', '', $state ?? '0'))
                                    ->live(debounce: 500),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Rincian')
                            ->reorderable(false)
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $details = $get('detail_biaya');

                                $total = collect($details)
                                    ->map(fn($item) => (int) preg_replace('/[^0-9]/', '', $item['jumlah'] ?? '0'))
                                    ->sum();

                                // SET DALAM FORMAT TAMPILAN (dengan pemisah ribuan)
                                $set('amount', number_format($total, 0, ',', '.'));
                            }),

                        TextInput::make('amount')
                            ->label('Total Rincian Biaya')
                            ->prefix('Rp')
                            ->readOnly()
                            // Saat menyimpan, ubah kembali ke angka murni
                            ->dehydrateStateUsing(fn($state) => (int) preg_replace('/\D/', '', (string) $state))
                            // Saat initial fill (edit), tampilkan terformat
                            ->formatStateUsing(fn($state) => number_format((int) ($state ?? 0), 0, ',', '.'))
                            ->helperText('Nilai ini dihitung otomatis oleh sistem berdasarkan rincian di atas.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')->label('Nomor Kwitansi')->searchable(),
                TextColumn::make('nama_pemohon_kwitansi')->label('Nama Pemohon')->searchable(),
                TextColumn::make('amount')->label('Jumlah')->money('IDR'),
                BadgeColumn::make('status_pembayaran')->label('Status Pembayaran')
                    ->colors([
                        'success' => PembayaranStatus::LUNAS->value,
                        'danger' => PembayaranStatus::BELUM_LUNAS->value,
                    ]),
            ])
            ->actions([
                // (Kita akan menambahkan kembali aksi Edit/Lengkapi nanti jika diperlukan)
                Action::make('download')
                    ->label('Download Kwitansi')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Receipt $record) => route('kwitansi.download', ['receipt' => $record]), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKwitansis::route('/'),
            'create' => Pages\CreateKwitansi::route('/create'),
            'edit' => Pages\EditKwitansi::route('/{record}/edit'),
        ];
    }
}