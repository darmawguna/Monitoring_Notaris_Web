<?php

namespace App\Filament\Resources\KwitansiResource\Pages;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource;
use Filament\Actions;
use Filament\Notifications\Notification; // <-- Tambahkan ini
use Filament\Resources\Pages\EditRecord;

class EditKwitansi extends EditRecord
{
    protected static string $resource = KwitansiResource::class;

    // --- TAMBAHKAN FUNGSI MOUNT INI ---
    /**
     * Logika ini berjalan sebelum halaman dirender.
     * Ia akan memeriksa status kwitansi.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Jika record (Kwitansi) sudah LUNAS
        if ($this->record->status_pembayaran === PembayaranStatus::LUNAS) {
            // Kirim notifikasi peringatan
            Notification::make()
                ->title('Kwitansi Sudah Lunas')
                ->body('Kwitansi yang sudah lunas tidak dapat diubah lagi.')
                ->warning()
                ->send();

            // Lemparkan pengguna kembali ke halaman 'view' (read-only)
            $this->redirect(KwitansiResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Hapus" tetap ada di sini (hanya untuk Superadmin)
            Actions\DeleteAction::make()
                ->visible(fn(): bool => auth()->user()->role->name === 'Superadmin'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Arahkan kembali ke halaman 'view' dari record yang baru saja diedit
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}