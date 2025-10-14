<?php

namespace App\Filament\Resources\PerbankanResource\Pages;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use App\Filament\Resources\PerbankanResource;
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

class ViewPerbankan extends ViewRecord
{
    protected static string $resource = PerbankanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('processTask')
                ->label('Proses Tugas Ini')
                ->icon('heroicon-o-arrow-right-circle')
                // Logika visibilitas dan form Anda sudah benar, tidak perlu diubah
                ->visible(function (): bool {
                    $user = auth()->user();
                    $record = $this->getRecord();
                    if (in_array($user->role->name, ['Superadmin', 'FrontOffice', 'Petugas Entry'])) {
                        return false;
                    }
                    return $record->progress()->where('assignee_id', $user->id)->where('status', 'pending')->exists();
                })
                ->form(function () {
                    $record = $this->getRecord();
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
                    return [Textarea::make('notes')->label('Catatan Pengerjaan Final')->required()];
                })

                // --- INI BAGIAN YANG DIPERBARUI SECARA TOTAL ---
                ->action(function (array $data): void {
                    $user = auth()->user();
                    /** @var \App\Models\Perbankan $record */
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
                        // 4. Jika TIDAK ADA tahap selanjutnya, buat tugas "Penyerahan" untuk Petugas Entry
                        $record->update([
                            'status_overall' => BerkasStatus::SELESAI,
                            'current_stage_key' => StageKey::PENYERAHAN,
                        ]);

                        // Ganti 'Petugas Entry' dengan nama peran Anda yang benar
                        $frontOfficeUsers = User::whereHas('role', fn($q) => $q->where('name', 'Petugas Entry'))->get();

                        foreach ($frontOfficeUsers as $foUser) {
                            $record->progress()->create([
                                'stage_key' => StageKey::PENYERAHAN,
                                'status' => 'pending',
                                'assignee_id' => $foUser->id,
                                'notes' => 'Berkas Perbankan telah menyelesaikan alur kerja dan siap untuk diserahkan/diarsipkan.',
                            ]);
                        }

                        if ($frontOfficeUsers->isNotEmpty()) {
                            // Gunakan 'nama_debitur' sebagai identifier untuk notifikasi Perbankan
                            $identifier = $record->nama_debitur;
                            Notification::make()
                                ->title('Berkas Selesai: Siap untuk Penyerahan')
                                ->body("Berkas Perbankan '{$identifier}' telah menyelesaikan alur pengerjaan.")
                                ->icon('heroicon-o-building-office-2') // Gunakan ikon yang relevan
                                ->sendToDatabase($frontOfficeUsers);
                        }
                    }

                    Notification::make()->title('Tugas berhasil diproses')->success()->send();

                    // Arahkan kembali ke halaman "Tugas Saya"
                    $this->redirect(TugasResource::getUrl('index'));
                }),
        ];
    }
}