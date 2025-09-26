<?php

namespace App\Filament\Resources\TugasResource\Pages;

use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\PerbankanResource;
use App\Filament\Resources\TandaTerimaSertifikatResource;
use App\Filament\Resources\TugasResource;
use App\Filament\Resources\TurunWarisResource;
use App\Models\Berkas;
use App\Models\Perbankan;
use App\Models\Progress;
use App\Models\TandaTerimaSertifikat;
use App\Models\TurunWaris;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTugas extends ViewRecord
{
    protected static string $resource = TugasResource::class;

    public function getTitle(): string
    {
        /** @var Progress $record */
        $record = $this->record;
        $parent = $record->progressable;

        // Membuat judul halaman yang dinamis
        $identifier = match (get_class($parent)) {
            Berkas::class => $parent->nomor_berkas,
            Perbankan::class => $parent->nama_debitur,
            TurunWaris::class => $parent->nama_kasus,
            TandaTerimaSertifikat::class => $parent->penerima,
            default => 'N/A',
        };

        return "Detail Tugas: " . $identifier;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        /** @var Progress $record */
        $record = $this->getRecord();
        $parentRecord = $record->progressable; // Ambil record induk (Berkas, Perbankan, dll.)
        $parentClass = get_class($parentRecord);

        // Tentukan Resource yang sesuai berdasarkan kelas model induk
        $resourceClass = match ($parentClass) {
            Berkas::class => BerkasResource::class,
            Perbankan::class => PerbankanResource::class,
            TurunWaris::class => TurunWarisResource::class,
            TandaTerimaSertifikat::class => TandaTerimaSertifikatResource::class,
            default => null,
        };

        // Jika resource yang sesuai ditemukan, "pinjam" skema infolist-nya
        if ($resourceClass) {
            return $resourceClass::infolist($infolist->record($parentRecord));
        }

        // Fallback jika model tidak dikenal
        return $infolist->schema([
            Section::make('Informasi Tugas')
                ->schema([
                    TextEntry::make('stage_key')->label('Tahap'),
                    TextEntry::make('deadline')->label('Deadline')->date('d F Y'),
                    TextEntry::make('progressable_type')->label('Tipe Dokumen'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var Progress $record */
        $record = $this->record;
        $parentRecord = $record->progressable;

        // Hanya tampilkan tombol jika tugas masih 'pending'
        if ($record->status !== 'pending') {
            return [];
        }

        return [
            Action::make('process')
                ->label('Proses Tugas')
                ->icon('heroicon-o-arrow-right-circle')
                ->form(function () use ($parentRecord) {
                    $nextRoleName = match ($parentRecord->current_stage_key) {
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
                ->action(function (array $data) use ($record, $parentRecord) {
                    // 1. Selesaikan tugas saat ini
                    $record->update([
                        'notes' => $data['notes'],
                        'status' => 'done',
                        'completed_at' => now(),
                    ]);

                    // 2. Tentukan tahap selanjutnya
                    $nextStage = match ($parentRecord->current_stage_key) {
                        StageKey::PETUGAS_2 => StageKey::PAJAK,
                        StageKey::PAJAK => StageKey::PETUGAS_5,
                        default => null,
                    };
                    $nextAssigneeId = $data['next_assignee_id'] ?? null;

                    // 3. Jika ada tahap selanjutnya, buat tugas baru
                    if ($nextStage && $nextAssigneeId) {
                        $parentRecord->progress()->create([
                            'stage_key' => $nextStage,
                            'status' => 'pending',
                            'assignee_id' => $nextAssigneeId,
                        ]);
                        $parentRecord->update(['current_stage_key' => $nextStage]);

                        // Kirim notifikasi
                        $nextAssignee = User::find($nextAssigneeId);
                        Notification::make()->title('Anda menerima tugas baru!')->sendToDatabase($nextAssignee);
                    } else {
                        // Jika tidak ada, selesaikan record induk
                        $parentRecord->update(['status_overall' => 'selesai', 'current_stage_key' => StageKey::SELESAI]);
                    }
                    Notification::make()->title('Tugas berhasil diproses')->success()->send();

                    // Arahkan kembali ke halaman daftar tugas
                    $this->redirect(TugasResource::getUrl('index'));
                }),
        ];
    }
}