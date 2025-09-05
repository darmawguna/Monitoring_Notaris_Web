<?php

namespace App\Http\Responses;

use App\Filament\Resources\BerkasResource;
use App\Filament\Resources\TugasResource;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        // Tentukan URL tujuan berdasarkan peran pengguna
        $redirectUrl = match ($user->role->name) {
            'FrontOffice' => BerkasResource::getUrl('create'),
            'Petugas2', 'Pajak', 'Petugas5' => TugasResource::getUrl('index'),
            // Default untuk Superadmin dan peran lainnya
            default => '/admin/laporan', // Menggunakan path statis yang sudah kita buat
        };

        return redirect()->intended($redirectUrl);
    }
}