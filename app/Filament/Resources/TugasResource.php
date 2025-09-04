<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TugasResource\Pages;
use App\Filament\Resources\TugasResource\RelationManagers;
use App\Models\Berkas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use App\Enums\StageKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Actions\Action as NotificationAction; // Perhatikan alias 'as'


class TugasResource extends Resource
{
    protected static ?string $model = Berkas::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Tugas Saya';
    protected static ?string $navigationLabel = 'Tugas Saya';
    protected static ?int $navigationSort = -1;

    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        return !in_array($userRole, ['Superadmin', 'FrontOffice']);
    }


    /**
     * INTI DARI FITUR "TUGAS SAYA":
     * Memodifikasi query dasar untuk resource ini agar hanya menampilkan
     * berkas yang ditugaskan kepada pengguna yang sedang login.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('current_assignee_id', auth()->id());
    }

    // Kita tidak butuh form di sini, jadi kita biarkan kosong.
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // Konfigurasi tabel untuk halaman "Tugas Saya".
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('No.')
                //     ->rowIndex()
                //     ->formatStateUsing(function (HasTable $livewire, string $state): string {
                //         $currentPage = $livewire->getTable()->getRecords()->currentPage();
                //         $perPage = $livewire->getTable()->getRecords()->perPage();
                //         return (string) (($currentPage - 1) * $perPage + (int) $state + 1);
                //     }),
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
            ->filters([
                // Filter tidak diperlukan karena data sudah terfilter otomatis.
            ])
            ->actions([
                ViewAction::make()->url(fn(Berkas $record) => BerkasResource::getUrl('view', ['record' => $record])),

                // --- INI ADALAH AKSI WORKFLOW BARU ---
                Action::make('process')
                    ->label('Proses Berkas')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->form(function (Berkas $record) {
                        // ... (logika form tetap sama)
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
                        // ... (logika update progres dan berkas tetap sama)
                        $currentProgress = $record->progress()->where('assignee_id', auth()->id())->latest()->first();
                        if ($currentProgress) {
                            $currentProgress->update(['notes' => $data['notes'], 'status' => 'done', 'completed_at' => now()]);
                        }
                        $nextStage = null;
                        $nextAssigneeId = $data['next_assignee_id'] ?? null;
                        if ($record->current_stage_key === StageKey::PETUGAS_2)
                            $nextStage = StageKey::PAJAK;
                        if ($record->current_stage_key === StageKey::PAJAK)
                            $nextStage = StageKey::PETUGAS_5;

                        // --- LOGIKA NOTIFIKASI DIMULAI DARI SINI ---
            
                        // Dapatkan objek pengguna yang akan menerima tugas
                        $nextAssignee = $nextAssigneeId ? User::find($nextAssigneeId) : null;

                        if ($nextStage && $nextAssignee) {
                            // ... (logika pembuatan progres baru dan update berkas)
                            $record->progress()->create(['stage_key' => $nextStage, 'status' => 'pending', 'assignee_id' => $nextAssigneeId, 'started_at' => now()]);
                            $record->update(['current_stage_key' => $nextStage, 'current_assignee_id' => $nextAssigneeId]);

                            // Kirim notifikasi ke petugas selanjutnya
                            Notification::make()
                                ->title('Anda menerima tugas baru!')
                                ->body("Berkas '{$record->nama_berkas}' telah diteruskan kepada Anda.")
                                ->icon('heroicon-o-inbox-arrow-down')
                                ->actions([
                                    NotificationAction::make('view')
                                        ->label('Lihat Tugas')
                                        ->url(TugasResource::getUrl('index')) // Arahkan ke halaman "Tugas Saya"
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($nextAssignee); // Kirim ke pengguna spesifik
            
                        } else {
                            // ... (logika jika ini tahap terakhir)
                            $record->update(['status_overall' => 'selesai', 'current_stage_key' => StageKey::SELESAI, 'current_assignee_id' => null]);
                            // Cari semua Superadmin
                            $superadmins = User::whereHas('role', fn($query) => $query->where('name', 'Superadmin'))->get();

                            // Kirim notifikasi ke semua Superadmin
                            Notification::make()
                                ->title('Sebuah Berkas Telah Selesai!')
                                ->body("Berkas '{$record->nama_berkas}' telah menyelesaikan seluruh alur kerja.")
                                ->icon('heroicon-o-check-badge')
                                ->actions([
                                    NotificationAction::make('view')
                                        ->label('Lihat Berkas')
                                        // Arahkan ke halaman detail berkas yang sudah selesai
                                        ->url(BerkasResource::getUrl('view', ['record' => $record]))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($superadmins); // Kirim ke koleksi pengguna
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
