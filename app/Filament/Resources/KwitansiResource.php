<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KwitansiResource\Pages;
use App\Models\Receipt;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;

class KwitansiResource extends Resource
{
    protected static ?string $model = Receipt::class;

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $modelLabel = 'Kwitansi';
    protected static ?string $pluralModelLabel = 'Audit Kwitansi';
    protected static ?string $navigationLabel = 'Kwitansi';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 3;

    /**
     * Sembunyikan menu ini dari semua orang kecuali Superadmin.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    // Kita tidak akan membuat kwitansi dari sini, jadi form bisa sederhana
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Hilangkan tombol "New Kwitansi" karena dibuat otomatis
            ->headerActions([])
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('Nomor Kwitansi')
                    ->searchable(),
                TextColumn::make('berkas.nomor')
                    ->label('Nomor Berkas')
                    ->searchable()
                    ->url(fn (Receipt $record): string => BerkasResource::getUrl('view', ['record' => $record->berkas])),
                TextColumn::make('amount')
                    ->label('Total Biaya (Seharusnya)')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('berkas.total_paid')
                    ->label('Total Sudah Dibayar')
                    ->money('IDR'),
                TextColumn::make('issuedBy.name')
                    ->label('Dibuat Oleh'),
                TextColumn::make('issued_at')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('updatePayment')
                    ->label('Update Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        TextInput::make('total_paid')
                            ->label('Jumlah Uang yang Masuk')
                            ->required()
                            // Hapus ->numeric() dari sini untuk menghindari konflik
                            ->prefix('Rp')
                            ->helperText('Masukkan jumlah yang sudah dibayar oleh klien.')
                            // Isi field ini dengan nilai saat ini agar mudah diedit
                            ->default(fn(Receipt $record) => $record->berkas->total_paid)
                            // Tambahkan mask untuk format Rupiah
                            ->mask(RawJs::from('$money($input, \',\')'))
                            // Hapus koma di sisi klien
                            ->stripCharacters(',')
                            // Bersihkan nilai di sisi server sebelum digunakan
                            ->dehydrateStateUsing(fn(string $state): string => preg_replace('/[^0-9]/', '', $state)),
                    ])
                    ->action(function (Receipt $record, array $data): void {
                        $record->berkas->update([
                            'total_paid' => $data['total_paid']
                        ]);
                        Notification::make()
                            ->title('Status pembayaran berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKwitansis::route('/'),
        ];
    }    
}