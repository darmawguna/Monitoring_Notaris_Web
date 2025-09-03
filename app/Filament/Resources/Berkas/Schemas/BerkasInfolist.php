<?php

namespace App\Filament\Resources\Berkas\Schemas;

// Impor komponen infolist dari namespace Infolists
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;

class BerkasInfolist
{
    /**
     * Konfigurasi Infolist Schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Berkas & Klien')
                    ->schema([
                        // Menambahkan ->inlineLabel() pada setiap TextEntry
                        TextEntry::make('nama_berkas')->inlineLabel(),
                        TextEntry::make('nomor')->inlineLabel(),
                        TextEntry::make('penjual')->inlineLabel(),
                        TextEntry::make('pembeli')->inlineLabel(),
                        TextEntry::make('sertifikat_nama')->inlineLabel(),
                        TextEntry::make('persetujuan')->columnSpanFull()->inlineLabel(),
                    ])->columns(2),

                Section::make('Status & Finansial')
                    ->schema([
                        // Menambahkan ->inlineLabel() di sini juga
                        TextEntry::make('status_overall')->badge()->inlineLabel(),
                        TextEntry::make('current_stage_key')->label('Tahap Saat Ini')->badge()->inlineLabel(),
                        TextEntry::make('currentAssignee.name')->label('Ditugaskan Ke')->inlineLabel(),
                        TextEntry::make('total_cost')->money('IDR')->inlineLabel(),
                        TextEntry::make('total_paid')->money('IDR')->inlineLabel(),
                        TextEntry::make('deadline_at')->dateTime()->inlineLabel(),
                    ])->columns(2),

                Section::make('Lampiran Berkas')
                    ->schema([
                        RepeatableEntry::make('files')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Jenis Dokumen'),



                                // Komponen 1: Tampilkan ini HANYA JIKA file adalah gambar
                                ImageEntry::make('path')
                                    ->label('Pratinjau')
                                    ->disk('public')
                                    ->height(80)
                                    // Membuat seluruh gambar bisa diklik
                                    ->action(
                                        Action::make('previewImage')
                                            ->label('Lihat Ukuran Penuh')
                                            // Gunakan simpleModal untuk tampilan bersih tanpa tombol
                                            ->modalHeading('Pratinjau Lampiran')
                                            // Konten modal adalah Infolist sederhana dengan satu ImageEntry
                                            ->modalContent(
                                                fn($record) =>
                                                Infolist::make()
                                                    ->record($record)
                                                    ->schema([
                                                        ImageEntry::make('path')
                                                            ->hiddenLabel()
                                                            ->disk('public')
                                                            ->extraAttributes(['style' => 'max-height: 75vh;']),
                                                    ])
                                            )
                                    )
                                    // Logika visibilitas: hanya tampil jika file adalah gambar
                                    ->visible(function ($record): bool {
                                        if (!$record || !$record->path)
                                            return false;
                                        return Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg'], strtolower($record->path));
                                    }),

                                // Komponen 2: Tampilkan ini HANYA JIKA file BUKAN gambar
                                TextEntry::make('path')
                                    ->label('File')
                                    ->formatStateUsing(fn(?string $state): string => $state ? basename($state) : 'N/A')
                                    ->url(fn($record) => $record->path ? Storage::url($record->path) : null, true)
                                    ->color('primary')
                                    // Logika visibilitas: hanya tampil jika file BUKAN gambar
                                    ->visible(function ($record): bool {
                                        if (!$record || !$record->path)
                                            return false;
                                        return !Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg'], strtolower($record->path));
                                    }),

                            ])->columns(2),
                    ])->collapsible(),

                Section::make('Riwayat & Durasi Pengerjaan')
                    ->schema([
                        RepeatableEntry::make('progress')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('stage_key')->label('Tahapan'),
                                TextEntry::make('assignee.name')->label('Petugas'),
                                TextEntry::make('started_at')->label('Mulai Dikerjakan')->dateTime('d M Y, H:i'),
                                TextEntry::make('completed_at')->label('Selesai Dikerjakan')->dateTime('d M Y, H:i'),
                                TextEntry::make('duration')
                                    ->label('Durasi Pengerjaan')
                                    ->state(function ($record): string {
                                        if (!$record->started_at || !$record->completed_at) {
                                            return 'Dalam proses';
                                        }
                                        $start = Carbon::parse($record->started_at);
                                        $end = Carbon::parse($record->completed_at);
                                        return $start->diffForHumans($end, true);
                                    }),
                                TextEntry::make('notes')->label('Catatan')->columnSpanFull(),
                            ])
                            ->columns(5),
                    ])->collapsible(),
            ]);
    }
}

