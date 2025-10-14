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
use App\Models\Progress; // <-- Model baru
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
use App\Enums\BerkasStatus;

class TugasResource extends Resource
{
    // --- TAHAP 1: UBAH MODEL DASAR ---
    protected static ?string $model = Progress::class; // Model sekarang adalah Progress

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Tugas Saya';
    protected static ?string $navigationLabel = 'Tugas Saya';
    protected static ?int $navigationSort = -1;

    // --- TAHAP 2: PERBARUI ELOQUENT QUERY ---
    public static function getEloquentQuery(): Builder
    {
        // Query sekarang jauh lebih sederhana dan akurat
        return parent::getEloquentQuery()
            ->where('assignee_id', auth()->id())
            ->where('status', 'pending')
            // Eager load relasi polimorfik 'progressable' untuk efisiensi
            ->with(['progressable']);
    }
    public static function canViewAny(): bool
    {
        $userRole = auth()->user()->role->name;
        return !in_array($userRole, ['Superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Tetap kosong
    }

    // --- TAHAP 3: BANGUN ULANG KOLOM TABEL ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom untuk menampilkan nomor/identifier dari parent record
                TextColumn::make('progressable.identifier')
                    ->label('Nomor Dokumen')
                    ->state(function (Progress $record): string {
                        // Logika untuk menampilkan identifier yang benar
                        return match (get_class($record->progressable)) {
                            Berkas::class => $record->progressable->nomor_berkas,
                            Perbankan::class => $record->progressable->nama_debitur,
                            TurunWaris::class => $record->progressable->nama_kasus,
                            TandaTerimaSertifikat::class => $record->progressable->penerima,
                            default => 'N/A',
                        };
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Logika pencarian kustom di beberapa model
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
                            };
                            $query->where($column, 'like', "%{$search}%");
                        });
                    }),

                // Kolom untuk menampilkan jenis dokumen
                TextColumn::make('progressable.type')
                    ->label('Jenis Dokumen')
                    ->state(function (Progress $record): string {
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
            // --- TAHAP 4: ADAPTASI AKSI ---
            ->actions([
                Action::make('view')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(function (Progress $record): string {
                        // Arahkan ke halaman view dari resource yang benar
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
                        // Logika form tetap sama, tapi sekarang mengambil data dari parent record
                        $parent = $record->progressable;
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
                        return [Textarea::make('notes')->label('Catatan Pengerjaan Final')->required()];
                    })
                    ->action(function (Progress $record, array $data): void {
                        // Logika aksi sekarang bekerja pada record Progress dan parent-nya
                        $parentRecord = $record->progressable;

                        // 1. Selesaikan tugas saat ini (record Progress)
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
                            // 4. Jika TIDAK ADA tahap selanjutnya, buat tugas "Penyerahan" untuk Front Office
                            $parentRecord->update([
                                'status_overall' => BerkasStatus::SELESAI,
                                'current_stage_key' => StageKey::PENYERAHAN,
                            ]);

                            // Ganti 'Petugas Entry' dengan nama peran Anda yang benar jika berbeda
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
                                $identifier = $parentRecord->nomor_berkas ?? $parentRecord->nama_kasus ?? $parentRecord->nama_debitur ?? 'sebuah berkas';
                                Notification::make()
                                    ->title('Berkas Selesai: Siap untuk Penyerahan')
                                    ->body("Berkas '{$identifier}' telah menyelesaikan alur pengerjaan.")
                                    ->sendToDatabase($frontOfficeUsers);
                            }
                        }
                        Notification::make()->title('Tugas berhasil diproses')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        // Halaman 'view' tidak lagi relevan di sini karena kita mengarahkan ke resource parent
        return [
            'index' => Pages\ListTugas::route('/'),
        ];
    }
}