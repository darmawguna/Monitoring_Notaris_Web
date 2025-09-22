<?php

namespace App\Filament\Resources\TugasResource\Pages;

use App\Enums\StageKey;
use App\Filament\Resources\TugasResource;
use App\Models\Berkas;
use App\Models\DeadlineConfig;
use App\Models\User;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ViewTugas extends ViewRecord
{
    protected static string $resource = TugasResource::class;

    public function getTitle(): string
    {
        /** @var Berkas $record */
        $record = $this->record;
        return "Detail Tugas: {$record->nama_berkas}";
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Informasi Berkas')
                ->schema([
                    ViewEntry::make('berkasInfo')
                        ->hiddenLabel()
                        ->view('filament.infolists.sections.berkas-info-section'),
                ]),

            InfolistSection::make('Data Pihak Jual Beli')
                ->schema([
                    ViewEntry::make('jualBeliInfo')
                        ->hiddenLabel()
                        ->view('filament.infolists.sections.jual-beli-section'),
                ]),

            InfolistSection::make('Informasi Sertifikat')
                ->schema([
                    ViewEntry::make('sertifikatInfo')
                        ->hiddenLabel()
                        ->view('filament.infolists.sections.sertifikat-section'),
                ]),

            InfolistSection::make('Informasi PBB')
                ->schema([
                    ViewEntry::make('pbbInfo')
                        ->hiddenLabel()
                        ->view('filament.infolists.sections.pbb-section'),
                ]),

            InfolistSection::make('Informasi Bank')
                ->schema([
                    ViewEntry::make('bankInfo')
                        ->hiddenLabel()
                        ->view('filament.infolists.sections.bank-section'),
                ]),

            InfolistSection::make('Lampiran Berkas')
                ->schema([
                    RepeatableEntry::make('files')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('type')
                                ->label('Jenis Dokumen'),

                            // THUMBNAIL JIKA GAMBAR
                            ImageEntry::make('path')
                                ->label('Pratinjau')
                                ->disk('public')
                                ->height(80)
                                ->visible(
                                    fn($record): bool =>
                                    $record && $record->path &&
                                    preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $record->path) === 1
                                ),

                            // TEKS + LINK JIKA BUKAN GAMBAR
                            TextEntry::make('path')
                                ->label('File')
                                ->formatStateUsing(fn(?string $state): string => $state ? basename($state) : 'N/A')
                                ->url(fn($record) => $record->path ? Storage::url($record->path) : null, true)
                                ->color('primary')
                                ->visible(
                                    fn($record): bool =>
                                    $record && $record->path &&
                                    preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $record->path) !== 1
                                ),

                            // AKSI PREVIEW & UNDUH
                            InfolistActions::make([
                                InfolistAction::make('preview')
                                    ->label('Pratinjau')
                                    ->icon('heroicon-o-eye')
                                    ->modalContent(function ($record) {
                                        return Infolist::make()
                                            ->record($record)
                                            ->schema([
                                                ImageEntry::make('path')
                                                    ->hiddenLabel()
                                                    ->disk('public')
                                                    ->extraAttributes([
                                                        'style' => 'display:block;max-width:100%;height:auto;margin:auto;',
                                                    ]),
                                            ]);
                                    })
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(false)
                                    ->visible(
                                        fn($record): bool =>
                                        $record && $record->path &&
                                        preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $record->path) === 1
                                    ),

                                InfolistAction::make('download')
                                    ->label('Unduh')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->color('success')
                                    ->url(fn($record) => route('berkas-files.download', ['berkasFile' => $record]), true),
                            ])->label('Aksi')->alignment(Alignment::Center),
                        ])
                        ->columns(3),
                ])
                ->collapsible(),
        ]);
    }

    /**
     * Header actions: Proses Berkas (menggantikan "Riwayat & Durasi Pengerjaan")
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label('Proses Berkas')
                ->icon('heroicon-o-arrow-right-circle')
                ->form(function () {
                    /** @var Berkas $record */
                    $record = $this->record;

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

                    return [
                        Textarea::make('notes')->label('Catatan Pengerjaan Final')->required(),
                    ];
                })
                ->action(function (array $data) {
                    /** @var Berkas $record */
                    $record = $this->record;

                    // 1) Tutup progress aktif milik user ini
                    $currentProgress = $record->progress()
                        ->where('assignee_id', auth()->id())
                        ->where('status', 'pending')
                        ->latest('started_at')
                        ->first();

                    if (!$currentProgress) {
                        Notification::make()
                            ->title('Tidak ada tugas aktif untuk Anda pada berkas ini.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $currentProgress->update([
                        'notes' => $data['notes'] ?? null,
                        'status' => 'done',
                        'completed_at' => now(),
                    ]);

                    // 2) Tentukan next stage (jika ada) atau selesai total
                    $nextStage = null;
                    if ($record->current_stage_key === StageKey::PETUGAS_2)
                        $nextStage = StageKey::PAJAK;
                    if ($record->current_stage_key === StageKey::PAJAK)
                        $nextStage = StageKey::PETUGAS_5;

                    $nextAssigneeId = $data['next_assignee_id'] ?? null;
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

                        $record->update([
                            'current_stage_key' => $nextStage,
                            'current_assignee_id' => $nextAssigneeId,
                        ]);

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
                        // Selesai total
                        $record->update([
                            'status_overall' => 'selesai',
                            'current_stage_key' => StageKey::SELESAI,
                            'current_assignee_id' => null,
                        ]);

                        $superadmins = User::whereHas('role', fn($q) => $q->where('name', 'Superadmin'))->get();
                        Notification::make()
                            ->title('Sebuah Berkas Telah Selesai!')
                            ->body("Berkas '{$record->nama_berkas}' telah menyelesaikan seluruh alur kerja.")
                            ->icon('heroicon-o-check-badge')
                            ->actions([
                                NotificationAction::make('view')
                                    ->label('Lihat Berkas')
                                    ->url(\App\Filament\Resources\BerkasResource::getUrl('view', ['record' => $record]))
                                    ->markAsRead(),
                            ])
                            ->sendToDatabase($superadmins);
                    }

                    Notification::make()->title('Berkas berhasil diproses')->success()->send();
                })
                ->modalWidth('2xl'),
        ];
    }
}
