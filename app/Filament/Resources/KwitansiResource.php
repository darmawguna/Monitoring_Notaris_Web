<?php

namespace App\Filament\Resources;

use App\Enums\PembayaranStatus;
use App\Filament\Resources\KwitansiResource\Pages;
use App\Models\Receipt;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class KwitansiResource extends Resource
{
    protected static ?string $model = Receipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $modelLabel = 'Kwitansi';
    protected static ?string $pluralModelLabel = 'Audit Kwitansi';
    protected static ?string $navigationLabel = 'Kwitansi';
    protected static ?string $navigationGroup = 'Keuangan';
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        $userRole = $user->role->name;
        return in_array($userRole, ['Superadmin', 'Petugas Entry']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kwitansi')
                    ->schema([
                        TextInput::make('receipt_number')
                            ->label('Nomor Kwitansi')
                            ->placeholder('Akan digenerate otomatis')
                            ->readOnly()
                            ->columnSpan(1),
                        Select::make('status_pembayaran')
                            ->label('Status Pembayaran')
                            ->options(PembayaranStatus::class)
                            ->required()
                            ->default(PembayaranStatus::BELUM_LUNAS)
                            ->columnSpan(1),
                        TextInput::make('nama_pemohon_kwitansi')
                            ->label('Nama Pemohon')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('berkas_id')
                            ->label('Tautkan ke Berkas (Opsional)')
                            ->relationship('berkas', 'nomor_berkas')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        TextInput::make('notes_kwitansi')
                            ->label('Catatan Kwitansi')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('informasi_kwitansi')
                            ->label('Informasi peruntukan Kwitansi')
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Rincian Biaya')
                    ->schema([
                        Repeater::make('detail_biaya')
                            ->label('Item Rincian Biaya')
                            ->schema([
                                TextInput::make('deskripsi')->required(),
                                TextInput::make('jumlah')
                                    ->required()
                                    ->prefix('Rp')
                                    ->mask(RawJs::from('$money($input, \'.\')'))
                                    ->stripCharacters('.')
                                    ->dehydrateStateUsing(fn($state): string => preg_replace('/[^0-9]/', '', $state ?? '0'))
                                    ->live(debounce: 500),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Rincian')
                            ->reorderable(false)
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $details = $get('detail_biaya');
                                $total = collect($details)
                                    ->map(fn($item) => (int) preg_replace('/[^0-9]/', '', $item['jumlah'] ?? '0'))
                                    ->sum();
                                $set('amount', number_format($total, 0, ',', '.'));
                            }),
                        TextInput::make('amount')
                            ->label('Total Rincian Biaya')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrateStateUsing(fn($state) => (int) preg_replace('/\D/', '', (string) $state))
                            ->formatStateUsing(fn($state) => number_format((int) ($state ?? 0), 0, ',', '.'))
                            ->helperText('Nilai ini dihitung otomatis oleh sistem berdasarkan rincian di atas.'),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Kwitansi')
                    ->schema([
                        TextEntry::make('receipt_number')->label('Nomor Kwitansi'),
                        TextEntry::make('nama_pemohon_kwitansi')->label('Nama Pemohon'),
                        TextEntry::make('berkas.nomor_berkas')->label('Terkait Berkas'),
                        TextEntry::make('notes_kwitansi')->label('Catatan Kwitansi'),
                        TextEntry::make('status_pembayaran')
                            ->badge()
                            ->color(fn(PembayaranStatus $state): string => match ($state) {
                                PembayaranStatus::LUNAS => 'success',
                                PembayaranStatus::BELUM_LUNAS => 'danger',
                            }),
                        TextEntry::make('informasi_kwitansi')->label('Informasi Peruntukan')->columnSpanFull(),
                    ])->columns(2),
                InfolistSection::make('Rincian Biaya')
                    ->schema([
                        RepeatableEntry::make('detail_biaya')
                            ->label('Item Rincian Biaya')
                            ->schema([
                                TextEntry::make('deskripsi'),
                                TextEntry::make('jumlah')->money('IDR'),
                            ])
                            ->columns(2),
                        TextEntry::make('amount')
                            ->label('Total Rincian Biaya')
                            ->money('IDR')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->alignEnd(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')->label('Nomor Kwitansi')->searchable(),
                TextColumn::make('nama_pemohon_kwitansi')->label('Nama Pemohon')->searchable(),
                TextColumn::make('amount')->label('Jumlah')->money('IDR'),
                BadgeColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->color(fn(PembayaranStatus $state): string => match ($state) {
                        PembayaranStatus::LUNAS => 'success',
                        PembayaranStatus::BELUM_LUNAS => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options(PembayaranStatus::class)
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Receipt $record): bool => $record->status_pembayaran !== PembayaranStatus::LUNAS),
                Action::make('ubahStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->fillForm(fn(Receipt $record) => ['status_pembayaran' => $record->status_pembayaran])
                    ->form([
                        Select::make('status_pembayaran')->label('Status Pembayaran')->options(PembayaranStatus::class)->required(),
                    ])
                    ->action(function (Receipt $record, array $data): void {
                        $record->update(['status_pembayaran' => $data['status_pembayaran']]);
                    })
                    ->visible(fn(Receipt $record): bool => $record->status_pembayaran !== PembayaranStatus::LUNAS),
                Action::make('download')
                    ->label('Download Kwitansi')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Receipt $record) => route('kwitansi.download', $record), shouldOpenInNewTab: true),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('tandai_lunas')
                        ->label('Tandai Lunas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->where('status_pembayaran', '!=', PembayaranStatus::LUNAS)
                                ->each->update(['status_pembayaran' => PembayaranStatus::LUNAS]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('tandai_belum_lunas')
                        ->label('Tandai Belum Lunas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->where('status_pembayaran', '!=', PembayaranStatus::LUNAS)
                                ->each->update(['status_pembayaran' => PembayaranStatus::BELUM_LUNAS]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->role->name === 'Superadmin'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKwitansis::route('/'),
            'create' => Pages\CreateKwitansi::route('/create'),
            'edit' => Pages\EditKwitansi::route('/{record}/edit'),
            'view' => Pages\ViewKwitansi::route('/{record}'), // <-- Daftarkan halaman view
        ];
    }
}

