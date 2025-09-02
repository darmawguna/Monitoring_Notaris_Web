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
            ['name' => 'FrontOffice', 'description' => 'Mengelola input berkas baru.'],
            ['name' => 'Petugas2', 'description' => 'Memproses berkas tahap kedua.'],
            ['name' => 'Pajak', 'description' => 'Memproses berkas terkait pajak.'],
            ['name' => 'Petugas5', 'description' => 'Memproses berkas tahap akhir.'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}