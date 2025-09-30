<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\DeadlineConfig;
use App\Filament\Resources\TugasResource;
class ViewBerkas extends ViewRecord
{
    protected static string $resource = BerkasResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('processTask')
                ->label('Proses Tugas Ini')
                ->icon('heroicon-o-arrow-right-circle')
                // 1. Logika Visibilitas: Tentukan siapa yang bisa melihat tombol ini
                ->visible(function (): bool {
                    $user = auth()->user();
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();

                    // Sembunyikan untuk Superadmin dan FrontOffice
                    if (in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
                        return false;
                    }

                    // Tampilkan hanya jika petugas ini punya tugas 'pending' di berkas ini
                    return $record->progress()
                        ->where('assignee_id', $user->id)
                        ->where('status', 'pending')
                        ->exists();
                })
                // 2. Logika Form: Sama persis seperti di TugasResource
                ->form(function () {
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();
                    $nextRoleName = match ($record->current_stage_key) {
                        StageKey::PETUGAS_2 => 'Pajak',
                        StageKey::PAJAK => 'Petugas5',
                        default => null,
                    };
                    if ($nextRoleName) {
                        return [
                            Textarea::make('notes')->label('Catatan Pengerjaan')->required(),
                            Select::make('next_assignee_id')
                                ->label("Teruskan ke Petugas {$nextRoleName}")
                                ->options(User::whereHas('role', fn($q) => $q->where('name', $nextRoleName))->pluck('name', 'id'))
                                ->searchable()->preload()->required(),
                        ];
                    }
                    return [Textarea::make('notes')->label('Catatan Pengerjaan Final')->required()];
                })
                // 3. Logika Aksi: Sama persis seperti di TugasResource
                ->action(function (array $data): void {
                    $user = auth()->user();
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();

                    // Selesaikan tugas saat ini
                    $currentProgress = $record->progress()->where('assignee_id', $user->id)->where('status', 'pending')->first();
                    if ($currentProgress) {
                        $currentProgress->update([
                            'notes' => $data['notes'],
                            'status' => 'done',
                            'completed_at' => now(),
                        ]);
                    }

                    // Tentukan & buat tugas selanjutnya (jika ada)
                    $nextStage = match ($record->current_stage_key) {
                        StageKey::PETUGAS_2 => StageKey::PAJAK,
                        StageKey::PAJAK => StageKey::PETUGAS_5,
                        default => null,
                    };
                    $nextAssigneeId = $data['next_assignee_id'] ?? null;

                    if ($nextStage && $nextAssigneeId) {
                        $deadlineDays = DeadlineConfig::where('stage_key', $nextStage)->value('default_days') ?? 3;
                        $deadline = Carbon::now()->addDays($deadlineDays);

                        $record->progress()->create([
                            'stage_key' => $nextStage,
                            'status' => 'pending',
                            'assignee_id' => $nextAssigneeId,
                            'deadline' => $deadline,
                        ]);
                        $record->update(['current_stage_key' => $nextStage]);

                        // Kirim Notifikasi
                        $nextAssignee = User::find($nextAssigneeId);
                        Notification::make()->title('Anda menerima tugas baru!')->sendToDatabase($nextAssignee);
                    } else {
                        $record->update(['status_overall' => 'selesai', 'current_stage_key' => StageKey::SELESAI]);
                    }
                    Notification::make()->title('Tugas berhasil diproses')->success()->send();

                    // 4. Redirect kembali ke halaman "Tugas Saya"
                    $this->redirect(TugasResource::getUrl('index'));
                }),

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