<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ViewBerkas extends ViewRecord
{
    protected static string $resource = BerkasResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('createReceipt')
                ->label('Buat Kwitansi')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                // Hanya tampilkan tombol ini jika berkas BELUM punya kwitansi
                ->visible(fn(): bool => !$this->record->receipt()->exists())
                // Definisikan form di dalam modal
                ->form([
                    TextInput::make('receipt_number')
                        ->label('Nomor Kwitansi')
                        ->default('KW-' . strtoupper(Str::random(8)))
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah Dibayar')
                        ->required()
                        ->numeric()
                        ->prefix('Rp')
                        ->helperText('Masukkan angka saja, tanpa titik atau koma.'),
                    DatePicker::make('issued_at')
                        ->label('Tanggal Dikeluarkan')
                        ->required()
                        ->default(now()),
                    Select::make('payment_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'cash' => 'Tunai (Cash)',
                            'transfer' => 'Transfer Bank',
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan Kwitansi')
                        ->columnSpanFull(),
                ])
                // Definisikan logika saat form di-submit
                ->action(function (array $data): void {
                    // 1. Buat record baru di tabel 'receipts' melalui relasi
                    $this->record->receipt()->create([
                        'receipt_number' => $data['receipt_number'],
                        'amount' => $data['amount'],
                        'issued_at' => $data['issued_at'],
                        'payment_method' => $data['payment_method'],
                        'notes' => $data['notes'],
                        'issued_by' => auth()->id(), // Ambil ID pengguna saat ini
                    ]);

                    // 2. Update kolom 'total_paid' di tabel 'berkas'
                    $this->record->update([
                        'total_paid' => $data['amount']
                    ]);

                    Notification::make()
                        ->title('Kwitansi berhasil dibuat')
                        ->success()
                        ->send();
                }),
        ];
    }
}