<?php

namespace App\Filament\Resources;

use App\Enums\StageKey;
use App\Filament\Resources\TugasResource\Pages;
use App\Models\Berkas;
use App\Models\DeadlineConfig;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TugasResource extends Resource
{
    protected static ?string $model = Berkas::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Tugas Saya';
    protected static ?string $navigationLabel = 'Tugas Saya';
    protected static ?int $navigationSort = -1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('current_assignee_id', auth()->id())
            // Tambahkan with() untuk memuat relasi yang dibutuhkan di tabel.
            ->with(['currentAssignee']);
    }
    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        return !in_array($userRole, ['Superadmin', 'FrontOffice']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No.')
                    ->rowIndex()
                    ->formatStateUsing(function (HasTable $livewire, string $state): string {
                        $currentPage = $livewire->getTable()->getRecords()->currentPage();
                        $perPage = $livewire->getTable()->getRecords()->perPage();
                        return (string) (($currentPage - 1) * $perPage + (int) $state + 1);
                    }),
                TextColumn::make('nomor')
                    ->label('Nomor Berkas')
                    ->searchable(),
                TextColumn::make('nama_berkas')
                    ->searchable(),
                TextColumn::make('penjual'),
                BadgeColumn::make('current_stage_key')
                    ->label('Tahap Saat Ini'),
                TextColumn::make('deadline_at')
                    ->label('Deadline')
                    ->date('d M Y'),
            ])
            ->filters([])
            ->actions([
                // ViewAction::make()->url(fn(Berkas $record) => BerkasResource::getUrl('view', ['record' => $record])),
                Action::make('process')
                    ->label('Proses Berkas')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->form(function (Berkas $record) {
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
                                    ->options(User::whereHas('role', fn($query) => $query->where('name', $nextRoleName))->pluck('name', 'id'))
                                    ->searchable()->preload()->required(),
                            ];
                        }
                        return [
                            Textarea::make('notes')->label('Catatan Pengerjaan Final')->required(),
                        ];
                    })
                    ->action(function (Berkas $record, array $data): void {
                        // --- INI LOGIKA YANG DIPERBARUI ---
                        // 1. Cari progres terakhir yang masih 'pending' untuk pengguna ini, pada berkas ini.
                        $currentProgress = $record->progress()
                            ->where('assignee_id', auth()->id())
                            ->where('status', 'pending')
                            ->latest('started_at')
                            ->first();

                        // 2. Jika ditemukan, update sebagai 'done'.
                        if ($currentProgress) {
                            $currentProgress->update([
                                'notes' => $data['notes'],
                                'status' => 'done',
                                'completed_at' => now(),
                            ]);
                        } else {
                            // Jika tidak ditemukan karena alasan aneh, beri tahu user dan hentikan.
                            Notification::make()->title('Tidak ada tugas aktif yang ditemukan untuk Anda pada berkas ini.')->danger()->send();
                            return;
                        }
                        // --- AKHIR DARI PERUBAHAN ---
            
                        // Logika untuk meneruskan tugas
                        $nextStage = null;
                        $nextAssigneeId = $data['next_assignee_id'] ?? null;
                        if ($record->current_stage_key === StageKey::PETUGAS_2)
                            $nextStage = StageKey::PAJAK;
                        if ($record->current_stage_key === StageKey::PAJAK)
                            $nextStage = StageKey::PETUGAS_5;

                        $nextAssignee = $nextAssigneeId ? User::find($nextAssigneeId) : null;

                        if ($nextStage && $nextAssignee) {
                            $deadlineDays = DeadlineConfig::where('stage_key', $nextStage)->value('default_days') ?? 3;
                            $startedAt = now();
                            $deadline = Carbon::parse($startedAt)->addDays($deadlineDays);

                            $record->progress()->create([
                                'stage_key' => $nextStage,
                                'status' => 'pending',
                                'assignee_id' => $nextAssigneeId,
                                'started_at' => $startedAt,
                                'deadline' => $deadline,
                            ]);

                            $record->update(['current_stage_key' => $nextStage, 'current_assignee_id' => $nextAssigneeId]);

                            Notification::make()
                                ->title('Anda menerima tugas baru!')
                                ->body("Berkas '{$record->nama_berkas}' telah diteruskan kepada Anda.")
                                ->icon('heroicon-o-inbox-arrow-down')
                                ->actions([
                                    NotificationAction::make('view')
                                        ->label('Lihat Tugas')
                                        ->url(TugasResource::getUrl('index'))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($nextAssignee);
                        } else {
                            $record->update(['status_overall' => 'selesai', 'current_stage_key' => StageKey::SELESAI, 'current_assignee_id' => null]);
                            $superadmins = User::whereHas('role', fn($query) => $query->where('name', 'Superadmin'))->get();
                            Notification::make()
                                ->title('Sebuah Berkas Telah Selesai!')
                                ->body("Berkas '{$record->nama_berkas}' telah menyelesaikan seluruh alur kerja.")
                                ->icon('heroicon-o-check-badge')
                                ->actions([
                                    NotificationAction::make('view')
                                        ->label('Lihat Berkas')
                                        ->url(BerkasResource::getUrl('view', ['record' => $record]))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($superadmins);
                        }
                        Notification::make()->title('Berkas berhasil diproses')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTugas::route('/'),
        ];
    }
}
