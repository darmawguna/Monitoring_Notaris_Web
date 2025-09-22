<?php

namespace App\Filament\Resources;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource\Pages;
use App\Models\Receipt;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;

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
        return auth()->user()->role->name === 'Superadmin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([])
            ->columns([
                TextColumn::make('receipt_number')->label('Nomor Kwitansi'),
                TextColumn::make('berkas.nama_pemohon')->label('Nama Pemohon'),
                TextColumn::make('amount')->label('Jumlah')->money('IDR'),
                BadgeColumn::make('status_pembayaran')->label('Status Pembayaran')
                    ->colors([
                        'success' => PembayaranStatus::LUNAS->value,
                        'danger' => PembayaranStatus::BELUM_LUNAS->value,
                    ]),
            ])
            ->actions([
                // --- AKSI BARU UNTUK RINCIAN BIAYA ---
                Action::make('lengkapiRincian')
                    ->label('Lengkapi Rincian')
                    ->icon('heroicon-o-pencil-square')
                    // ->fillForm(function (Receipt $record): array {
                    //     // 1. Ambil data rincian yang sudah ada
                    //     $details = $record->detail_biaya ?? [];
                    //     // 2. Hitung total dari data rincian tersebut
                    //     $total = collect($details)->pluck('jumlah')->sum();
                    //     // 3. Kembalikan array yang berisi data untuk kedua field
                    //     return [
                    //         'detail_biaya' => $details,
                    //         'total_rincian' => $total,
                    //     ];
                    // })
                    ->mountUsing(function (Form $form, Receipt $record): void {
                        // 1. Ambil data rincian yang sudah ada dari record
                        $details = $record->detail_biaya ?? [];

                        // 2. Hitung total dari data rincian tersebut
                        $total = collect($details)->pluck('jumlah')->sum();

                        // 3. Isi seluruh form dengan data yang sudah disiapkan
                        $form->fill([
                            'detail_biaya' => $details,
                            'total_rincian' => $total,
                        ]);
                    })
                    ->form([
                        // --- INI BAGIAN YANG DIPERBARUI ---
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
                                    // 1. Buat input ini reaktif untuk memicu induknya
                                    ->live(debounce: 500),
                                // Hapus afterStateUpdated dari sini
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Rincian')
                            ->reorderable(false)
                            // 2. Jadikan Repeater yang reaktif dan bertanggung jawab atas kalkulasi
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $details = $get('detail_biaya');

                                // 1. Bersihkan setiap nilai 'jumlah' sebelum menjumlahkannya
                                $total = collect($details)
                                    ->map(fn($item) => (int) preg_replace('/[^0-9]/', '', $item['jumlah'] ?? '0'))
                                    ->sum();

                                // 2. Set total yang sudah bersih
                                $set('total_rincian', $total);
                            }),
                        // --- AKHIR DARI PERUBAHAN ---

                        TextInput::make('total_rincian')
                            ->label('Total Rincian Biaya')
                            ->prefix('Rp')
                            ->readOnly()
                            ->numeric()
                            ->formatStateUsing(fn(?string $state): string => number_format($state ?? 0, 0, ',', '.'))
                            ->helperText('Nilai ini dihitung otomatis oleh sistem berdasarkan rincian di atas.'),
                    ])
                    ->action(function (Receipt $record, array $data): void {
                        $totalRincian = collect($data['detail_biaya'])->pluck('jumlah')->sum();
                        $record->update([
                            'detail_biaya' => $data['detail_biaya'],
                            'amount' => $totalRincian,
                        ]);
                        Notification::make()->title('Rincian biaya berhasil diperbarui')->success()->send();
                    }),


                // Aksi download Anda yang sudah ada
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
        ];
    }
}