<?php

namespace App\Models;

// --- TAMBAHKAN 'USE' STATEMENTS INI ---
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// --- IMPLEMENTASIKAN KONTRAK FILAMENTUSER ---
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- FUNGSI BARU UNTUK OTORISASI PANEL FILAMENT ---
    /**
     * Tentukan apakah pengguna dapat mengakses Panel Admin Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Ambil domain yang diizinkan dari file .env
        $allowedDomain = env('USER_EMAIL_DOMAIN');

        // 2. Jika tidak ada domain yang diatur di .env, izinkan semua orang (untuk pengembangan)
        if (!$allowedDomain) {
            return true;
        }

        // 3. Izinkan akses hanya jika email pengguna diakhiri dengan domain yang diizinkan
        return str_ends_with($this->email, '@' . $allowedDomain);

        // Catatan: Jika Anda menerapkan verifikasi email di masa depan,
        // Anda bisa menambahkan pengecekan ini:
        // return str_ends_with($this->email, '@' . $allowedDomain) && $this->hasVerifiedEmail();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBerkas(): HasMany
    {
        return $this->hasMany(Berkas::class, 'created_by');
    }
}
