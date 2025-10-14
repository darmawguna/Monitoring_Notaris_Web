<?php

namespace App\Filament\Resources\PerbankanResource\Pages;
use App\Filament\Resources\PerbankanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\StageKey;
use App\Filament\Resources\TugasResource;
use App\Models\DeadlineConfig;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
class ViewPerbankan extends ViewRecord
{
    protected static string $resource = PerbankanResource::class;

    // Opsional: Tambahkan tombol "Edit" di pojok kanan atas halaman View
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('processTask')
                ->label('Proses Tugas Ini')
                ->icon('heroicon-o-arrow-right-circle')
                // 1. Logika Visibilitas
                ->visible(function (): bool {
                    $user = auth()->user();
                    /** @var \App\Models\Perbankan $record */
                    $record = $this->getRecord();

                    if (in_array($user->role->name, ['Superadmin', 'FrontOffice'])) {
                        return false;
                    }

                    return $record->progress()
                        ->where('assignee_id', $user->id)
                        ->where('status', 'pending')
                        ->exists();
                })
                // 2. Logika Form
                ->form(function () {
                    /** @var \App\Models\Perbankan $record */
                    $record = $this->getRecord();
                    $nextRoleName = match ($record->current_stage_key) {
                        StageKey::PETUGAS_PENGETIKAN => 'Petugas Pajak',
                        StageKey::PETUGAS_PAJAK => 'Petugas Penyiapan',
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
                // 3. Logika Aksi
                ->action(function (array $data): void {
                    $user = auth()->user();
                    /** @var \App\Models\Perbankan $record */
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

                    // Tentukan & buat tugas selanjutnya
                    $nextStage = match ($record->current_stage_key) {
                        StageKey::PETUGAS_PENGETIKAN => 'Petugas Pajak',
                        StageKey::PETUGAS_PAJAK => 'Petugas Penyiapan',
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

                        $nextAssignee = User::find($nextAssigneeId);
                        Notification::make()->title('Anda menerima tugas baru!')->sendToDatabase($nextAssignee);
                    } else {
                        $record->update(['status_overall' => 'selesai', 'current_stage_key' => StageKey::SELESAI]);
                    }
                    Notification::make()->title('Tugas berhasil diproses')->success()->send();

                    // 4. Redirect kembali ke halaman "Tugas Saya"
                    $this->redirect(TugasResource::getUrl('index'));
                }),
        ];
    }
}