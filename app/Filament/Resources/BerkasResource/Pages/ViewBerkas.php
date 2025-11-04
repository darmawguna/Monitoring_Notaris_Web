<?php

namespace App\Filament\Resources\BerkasResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\TugasResource;
use App\Models\DeadlineConfig;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewBerkas extends ViewRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Action::make('processTask')
                ->label('Proses Tugas Ini')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(function (): bool {
                    $user = auth()->user();
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();

                    // Sembunyikan untuk Superadmin dan Petugas Entry
                    if (in_array($user->role->name, ['Superadmin', 'Petugas Entry'])) {
                        return false;
                    }
                    // Tampilkan hanya jika petugas ini punya tugas 'pending' di berkas ini
                    return $record->progress()->where('assignee_id', $user->id)->where('status', 'pending')->exists();
                })

                // --- FORM DIPERBARUI ---
                ->form(function () {
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();

                    // Jika tahapnya PENYERAHAN, hanya tampilkan catatan
                    if ($record->current_stage_key === StageKey::PENYERAHAN) {
                        return [
                            Textarea::make('notes')->label('Catatan Pengerjaan Final')
                                ->helperText('Contoh: Telah diserahkan ke klien pada tanggal...')
                                ->required(),
                        ];
                    }

                    // Logika form untuk tahap-tahap sebelumnya
                    $nextRoleName = match ($record->current_stage_key) {
                        StageKey::PETUGAS_PENGETIKAN => 'Petugas Pajak',
                        StageKey::PETUGAS_PAJAK => 'Petugas Penyiapan',
                        default => null,
                    };
                    if ($nextRoleName) {
                        return [
                            Textarea::make('notes')->label('Catatan Pengerjaan')->required(),
                            Select::make('next_assignee_id')->label("Teruskan ke Petugas {$nextRoleName}")->options(User::whereHas('role', fn($q) => $q->where('name', $nextRoleName))->pluck('name', 'id'))->searchable()->preload()->required(),
                        ];
                    }
                    // Fallback untuk tahap terakhir sebelum penyerahan
                    return [Textarea::make('notes')->label('Catatan Pengerjaan Final')->required()];
                })

                // --- AKSI DIPERBARUI TOTAL ---
                ->action(function (array $data): void {
                    $user = auth()->user();
                    /** @var \App\Models\Berkas $record */
                    $record = $this->getRecord();

                    // 1. Selesaikan tugas saat ini
                    $currentProgress = $record->progress()->where('assignee_id', $user->id)->where('status', 'pending')->first();
                    if ($currentProgress) {
                        $currentProgress->update([
                            'notes' => $data['notes'],
                            'status' => 'done',
                            'completed_at' => now(),
                        ]);
                    }

                    // 2. Tentukan tahap selanjutnya
                    $nextStage = match ($record->current_stage_key) {
                        StageKey::PETUGAS_PENGETIKAN => StageKey::PETUGAS_PAJAK,
                        StageKey::PETUGAS_PAJAK => StageKey::PETUGAS_PENYIAPAN,
                        default => null,
                    };
                    $nextAssigneeId = $data['next_assignee_id'] ?? null;

                    // 3. Jika ADA tahap selanjutnya, teruskan seperti biasa
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

                        $nextAssignee = User::find($nextAssigneeId);
                        if ($nextAssignee) {
                            Notification::make()->title('Anda menerima tugas baru!')->sendToDatabase($nextAssignee);
                        }
                    } else {
                        // 4. Jika TIDAK ADA tahap selanjutnya...
        
                        // Periksa apakah tahap saat ini BUKAN penyerahan
                        if ($record->current_stage_key !== StageKey::PENYERAHAN) {
                            // Ini adalah petugas terakhir (misal: Petugas Penyiapan)
                            // Buat tugas "Penyerahan" untuk Petugas Entry
                            $record->update([
                                'status_overall' => BerkasStatus::SELESAI,
                                'current_stage_key' => StageKey::PENYERAHAN,
                            ]);

                            $frontOfficeUsers = User::whereHas('role', fn($q) => $q->where('name', 'Petugas Entry'))->get();

                            foreach ($frontOfficeUsers as $foUser) {
                                $record->progress()->create([
                                    'stage_key' => StageKey::PENYERAHAN,
                                    'status' => 'pending',
                                    'assignee_id' => $foUser->id,
                                    'notes' => 'Berkas telah menyelesaikan alur kerja dan siap untuk diserahkan/diarsipkan.',
                                ]);
                            }

                            if ($frontOfficeUsers->isNotEmpty()) {
                                $identifier = $record->nomor_berkas ?? 'sebuah berkas';
                                Notification::make()
                                    ->title('Berkas Selesai: Siap untuk Penyerahan')
                                    ->body("Berkas '{$identifier}' telah menyelesaikan alur pengerjaan.")
                                    ->sendToDatabase($frontOfficeUsers);
                            }
                        } else {
                            $record->update([
                                'status_overall' => BerkasStatus::SELESAI,
                            ]);
                        }
                    }

                    Notification::make()->title('Tugas berhasil diproses')->success()->send();

                    // Arahkan kembali ke halaman "Tugas Saya"
                    $this->redirect(TugasResource::getUrl('index'));
                }),
        ];
    }
}