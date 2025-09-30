<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Forms\Get;
use Filament\Forms\Set;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    // --- Konfigurasi Tampilan & Navigasi ---
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Manajemen Pengguna';
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $navigationGroup = 'Pengaturan'; // Grupkan di bawah menu "Pengaturan"
    protected static ?int $navigationSort = 2;

    /**
     * Sembunyikan menu ini dari semua orang kecuali Superadmin.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()->role->name === 'Superadmin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            // 1. Jadikan field nama reaktif, update saat fokus hilang (onBlur)
                            ->live(onBlur: true)
                            // 2. Setelah nama diisi, jalankan logika untuk membuat email
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $name = $get('name');
                                if ($name) {
                                    // Ambil domain dari file .env, dengan fallback default
                                    $domain = env('USER_EMAIL_DOMAIN', 'notarisflow.com');

                                    // Buat format email: nama.tanpa.spasi@domain.com
                                    // Contoh: "Darma Wiguna" -> "darma.wiguna"
                                    $emailName = Str::slug($name, '.');

                                    // Set nilai field 'email'
                                    $set('email', strtolower($emailName) . '@' . $domain);
                                }
                            }),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            // 3. Buat field email menjadi tidak bisa diedit
                            ->disabled()
                            ->dehydrated(), // Pastikan tetap tersimpan meskipun disabled
                        Select::make('role_id')
                            ->label('Peran')
                            ->relationship('role', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                BadgeColumn::make('role.name')
                    ->label('Peran'),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
