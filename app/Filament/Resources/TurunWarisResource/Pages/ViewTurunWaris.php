<?php

namespace App\Filament\Resources\TurunWarisResource\Pages;
use App\Filament\Resources\TurunWarisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\DeadlineConfig;
use Filament\Notifications\Notification;
use App\Enums\StageKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\TugasResource;
class ViewTurunWaris extends ViewRecord
{
    protected static string $resource = TurunWarisResource::class;

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
                    /** @var \App\Models\TurunWaris $record */
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