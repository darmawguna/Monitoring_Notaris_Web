<?php

namespace App\Filament\Resources;

use App\Enums\StageKey;
use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\PerbankanResource;
use App\Filament\Resources\TandaTerimaSertifikatResource;
use App\Filament\Resources\TugasResource\Pages;
use App\Filament\Resources\TurunWarisResource;
use App\Models\Berkas;
use App\Models\Perbankan;
use App\Models\Progress;
use App\Models\TandaTerimaSertifikat;
use App\Models\TurunWaris;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\BerkasStatus; // Pastikan use statement ini ada

class TugasResource extends Resource
{
    protected static ?string $model = Progress::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Tugas Saya';
    protected static ?string $navigationLabel = 'Tugas Saya';
    protected static ?int $navigationSort = -1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('assignee_id', auth()->id())
            ->where('status', 'pending')
            ->with(['progressable']);
    }

    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        // Izinkan semua peran melihat, KECUALI Superadmin
        return !in_array($userRole, ['Superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Tetap kosong
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('progressable.identifier')
                    ->label('Nomor Dokumen')
                    ->state(function (Progress $record): string {
                        // Periksa apakah relasi progressable ada sebelum mengakses propertinya
                        if (!$record->progressable) {
                            return 'N/A (Data Induk Hilang)';
                        }
                        return match (get_class($record->progressable)) {
                            Berkas::class => $record->progressable->nomor_berkas,
                            Perbankan::class => $record->progressable->nama_debitur,
                            TurunWaris::class => $record->progressable->nama_kasus,
                            TandaTerimaSertifikat::class => $record->progressable->penerima,
                            default => 'N/A',
                        };
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('progressable', [
                            Berkas::class,
                            Perbankan::class,
                            TurunWaris::class,
                            TandaTerimaSertifikat::class
                        ], function (Builder $query, string $type) use ($search) {
                            $column = match ($type) {
                                Berkas::class => 'nomor_berkas',
                                Perbankan::class => 'nama_debitur',
                                TurunWaris::class => 'nama_kasus',
                                TandaTerimaSertifikat::class => 'penerima',
                                default => 'id', // Fallback
                            };
                            $query->where($column, 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('progressable.type')
                    ->label('Jenis Dokumen')
                    ->state(function (Progress $record): string {
                        if (!$record->progressable) {
                            return 'Tidak Dikenal';
                        }
                        return match (get_class($record->progressable)) {
                            Berkas::class => 'Berkas Peralihan Hak',
                            Perbankan::class => 'Berkas Perbankan',
                            TurunWaris::class => 'Berkas Turun Waris',
                            TandaTerimaSertifikat::class => 'Tanda Terima Sertifikat',
                            default => 'Tidak Dikenal',
                        };
                    }),

                BadgeColumn::make('stage_key')->label('Tahap Saat Ini'),
                TextColumn::make('deadline')->date('d M Y'),
            ])
            ->filters([])
            ->actions([
                Action::make('view')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(function (Progress $record): string {
                        if (!$record->progressable) {
                            return '#';
                        }
                        $parent = $record->progressable;
                        return match (get_class($parent)) {
                            Berkas::class => BerkasResource::getUrl('view', ['record' => $parent]),
                            Perbankan::class => PerbankanResource::getUrl('view', ['record' => $parent]),
                            TurunWaris::class => TurunWarisResource::getUrl('view', ['record' => $parent]),
                            TandaTerimaSertifikat::class => TandaTerimaSertifikatResource::getUrl('view', ['record' => $parent]),
                            default => '#',
                        };
                    }),

                Action::make('process')
                    ->label('Proses Tugas')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->form(function (Progress $record) {
                        $parent = $record->progressable;

                        // Jika tahapnya PENYERAHAN, jangan tampilkan form "Teruskan ke"
                        if ($parent->current_stage_key === StageKey::PENYERAHAN) {
                            return [
                                Textarea::make('notes')->label('Catatan Pengerjaan Final')
                                    ->helperText('Contoh: Telah diserahkan ke klien pada tanggal...')
                                    ->required(),
                            ];
                        }

                        // Logika form untuk tahap-tahap sebelumnya
                        $nextRoleName = match ($parent->current_stage_key) {
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

                        // Fallback untuk tahap terakhir sebelum penyerahan
                        return [Textarea::make('notes')->label('Catatan Pengerjaan Final')->required()];
                    })
                    ->action(function (Progress $record, array $data): void {
                        $parentRecord = $record->progressable;

                        // 1. Selesaikan tugas saat ini
                        $record->update([
                            'notes' => $data['notes'],
                            'status' => 'done',
                            'completed_at' => now(),
                        ]);

                        // 2. Tentukan tahap selanjutnya
                        $nextStage = match ($parentRecord->current_stage_key) {
                            StageKey::PETUGAS_PENGETIKAN => StageKey::PETUGAS_PAJAK,
                            StageKey::PETUGAS_PAJAK => StageKey::PETUGAS_PENYIAPAN,
                            default => null,
                        };
                        $nextAssigneeId = $data['next_assignee_id'] ?? null;

                        // 3. Jika ADA tahap selanjutnya, teruskan seperti biasa
                        if ($nextStage && $nextAssigneeId) {
                            $deadlineDays = \App\Models\DeadlineConfig::where('stage_key', $nextStage)->value('default_days') ?? 3;
                            $deadline = \Illuminate\Support\Carbon::now()->addDays($deadlineDays);

                            $parentRecord->progress()->create([
                                'stage_key' => $nextStage,
                                'status' => 'pending',
                                'assignee_id' => $nextAssigneeId,
                                'deadline' => $deadline,
                            ]);
                            $parentRecord->update(['current_stage_key' => $nextStage]);

                            $nextAssignee = User::find($nextAssigneeId);
                            if ($nextAssignee) {
                                Notification::make()->title('Anda menerima tugas baru!')->sendToDatabase($nextAssignee);
                            }
                        } else {
                            // 4. Jika TIDAK ADA tahap selanjutnya...
            
                            // Periksa apakah tahap saat ini BUKAN penyerahan
                            if ($parentRecord->current_stage_key !== StageKey::PENYERAHAN) {
                                // Ini adalah petugas terakhir (misal: Petugas Penyiapan)
                                // Buat tugas "Penyerahan" untuk Petugas Entry
                                $parentRecord->update([
                                    'status_overall' => BerkasStatus::SELESAI,
                                    'current_stage_key' => StageKey::PENYERAHAN,
                                ]);

                                // Ganti 'Petugas Entry' dengan nama peran Anda yang benar
                                $frontOfficeUsers = User::whereHas('role', fn($q) => $q->where('name', 'Petugas Entry'))->get();

                                foreach ($frontOfficeUsers as $foUser) {
                                    $parentRecord->progress()->create([
                                        'stage_key' => StageKey::PENYERAHAN,
                                        'status' => 'pending',
                                        'assignee_id' => $foUser->id,
                                        'notes' => 'Berkas telah menyelesaikan alur kerja dan siap untuk diserahkan/diarsipkan.',
                                    ]);
                                }

                                if ($frontOfficeUsers->isNotEmpty()) {
                                    $identifier = match (get_class($parentRecord)) {
                                        Berkas::class => $parentRecord->nomor_berkas,
                                        Perbankan::class => $parentRecord->nama_debitur,
                                        TurunWaris::class => $parentRecord->nama_kasus,
                                        TandaTerimaSertifikat::class => $parentRecord->penerima,
                                        default => 'sebuah berkas',
                                    };
                                    Notification::make()
                                        ->title('Berkas Selesai: Siap untuk Penyerahan')
                                        ->body("Berkas '{$identifier}' telah menyelesaikan alur pengerjaan.")
                                        ->sendToDatabase($frontOfficeUsers);
                                }
                            } else {
                                // Jika tahap saat ini SUDAH PENYERAHAN, berarti
                                // Petugas Entry telah menyelesaikan tugas akhirnya.
                                // Cukup pastikan statusnya Selesai dan jangan lakukan apa-apa lagi.
                                $parentRecord->update([
                                    'status_overall' => BerkasStatus::SELESAI,
                                ]);
                            }
                        }

                        Notification::make()->title('Tugas berhasil diproses')->success()->send();
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
