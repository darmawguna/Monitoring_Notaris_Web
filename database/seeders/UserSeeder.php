<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kumpulan data pengguna yang akan kita buat
        $usersData = [
            [
                'name' => 'Hendy (Superadmin)',
                'email' => 'superadmin@example.com',
                'password' => 'password',
                'roleName' => 'Superadmin',
            ],
            [
                'name' => 'Petugas Front Office',
                'email' => 'frontoffice@example.com',
                'password' => 'password',
                'roleName' => 'FrontOffice',
            ],
            [
                'name' => 'Petugas Dua A',
                'email' => 'petugas2a@example.com',
                'password' => 'password',
                'roleName' => 'Petugas2',
            ],
            [
                'name' => 'Petugas Dua B',
                'email' => 'petugas2b@example.com',
                'password' => 'password',
                'roleName' => 'Petugas2',
            ],
            [
                'name' => 'Petugas Pajak',
                'email' => 'pajak@example.com',
                'password' => 'password',
                'roleName' => 'Pajak',
            ],
            [
                'name' => 'Petugas Lima',
                'email' => 'petugas5@example.com',
                'password' => 'password',
                'roleName' => 'Petugas5',
            ],
        ];

        foreach ($usersData as $userData) {
            // 1. Cari role berdasarkan nama, bukan ID
            $role = Role::where('name', $userData['roleName'])->first();

            // 2. Jika role ditemukan, buat user
            if ($role) {
                // Gunakan firstOrCreate untuk menghindari duplikat jika seeder dijalankan lagi
                User::firstOrCreate(
                    ['email' => $userData['email']], // Kondisi untuk mengecek
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make($userData['password']), // Enkripsi password
                        'role_id' => $role->id, // Gunakan ID dari role yang ditemukan
                    ]
                );
            }
        }
    }
}
