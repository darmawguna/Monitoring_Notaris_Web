<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Superadmin', 'description' => 'Akses penuh ke semua fitur sistem.'],
            ['name' => 'Petugas Entry', 'description' => 'Mengelola input berkas baru.'],
            ['name' => 'Petugas Pengetikan', 'description' => 'Memproses berkas tahap kedua.'],
            ['name' => 'Petugas Pajak', 'description' => 'Memproses berkas terkait pajak.'],
            ['name' => 'Petugas Penyiapan', 'description' => 'Memproses berkas tahap akhir.'],
            ['name' => 'Petugas BPN', 'description' => 'Memproses berkas tahap akhir.'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}