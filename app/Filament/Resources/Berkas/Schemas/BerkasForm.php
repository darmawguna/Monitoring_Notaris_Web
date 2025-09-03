<?php

namespace App\Filament\Resources\Berkas\Schemas;

use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Component;

class BerkasForm
{
    public static function configure(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Detail Klien & Penugasan') // Judul diubah agar lebih relevan
                    ->schema([
                        TextInput::make('nama_berkas')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nomor')
                            ->default('B-' . strtoupper(Str::random(8)))
                            ->readOnly()
                            ->required(),
                        TextInput::make('penjual')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('pembeli')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sertifikat_nama'),
                        Textarea::make('persetujuan')
                            ->columnSpanFull(),

                        // [TODO 3] Tambahkan dropdown untuk memilih petugas selanjutnya
                        Select::make('current_assignee_id')
                            ->label('Tugaskan ke Petugas 2')
                            ->relationship(
                                name: 'currentAssignee',
                                titleAttribute: 'name',
                                // Modifikasi query untuk hanya menampilkan user dengan peran 'Petugas2'
                                modifyQueryUsing: fn(Builder $query) => $query->whereHas(
                                    'role',
                                    fn(Builder $query) => $query->where('name', 'Petugas2')
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                    ])->columns(2),

                Section::make('Upload Lampiran Berkas')
                    ->schema([
                        Repeater::make('files')
                            ->relationship()
                            ->label('Lampiran Berkas')
                            ->schema([
                                Select::make('type')
                                    ->label('Jenis Dokumen')
                                    ->options([
                                        'ktp_suami' => 'KTP Suami',
                                        'ktp_istri' => 'KTP Istri',
                                        'kk' => 'Kartu Keluarga',
                                        'sertifikat' => 'Sertifikat',
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->required(),

                                FileUpload::make('path')
                                    ->label('Upload File')
                                    ->disk('public')
                                    ->directory('berkas-attachments')
                                    ->preserveFilenames()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Dokumen Lampiran'),
                        // <-- Hapus semua metode ->mutate... dari sini
                    ]),

                Section::make('Biaya')
                    ->schema([
                        // [TODO 1] Kode ini sudah benar dan tidak perlu diubah.
                        TextInput::make('total_cost')
                            ->label('Total Biaya')
                            ->required()
                            // ->numeric() // Aturan validasi tetap ada
                            ->prefix('Rp')
                            // Mask untuk format Rupiah (gunakan koma agar sesuai screenshot)
                            ->mask(RawJs::from('$money($input, \',\')'))
                            // Hapus koma di sisi klien sebelum mengirim
                            ->stripCharacters(',')
                            // Placeholder untuk memberi contoh
                            ->placeholder('2,000,000')
                            // INI SOLUSINYA: Bersihkan nilai di sisi server sebelum validasi
                            ->dehydrateStateUsing(fn(string $state): string => preg_replace('/[^0-9]/', '', $state)),
                    ]),
            ]);
    }
}

