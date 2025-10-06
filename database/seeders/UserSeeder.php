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
        // --- PERUBAHAN 1: Definisikan domain Anda di sini ---
        // Ambil domain dari .env, atau gunakan domain yang Anda berikan sebagai fallback
        $domain = env('USER_EMAIL_DOMAIN', 'lightslategray-bat-503082.hostingersite.com');

        $usersData = [
            [
                'name' => 'Superadmin',
                'email_prefix' => 'superadmin', // Gunakan prefix, bukan email lengkap
                'password' => 'password',
                'roleName' => 'Superadmin',
            ],
            [
                'name' => 'Petugas Front Office',
                'email_prefix' => 'frontoffice',
                'password' => 'password',
                'roleName' => 'Petugas Entry',
            ],
            [
                'name' => 'Petugas Pengetikan A',
                'email_prefix' => 'petugas2a',
                'password' => 'password',
                'roleName' => 'Petugas Pengetikan',
            ],
            [
                'name' => 'Petugas Pengetikan B',
                'email_prefix' => 'petugas2b',
                'password' => 'password',
                'roleName' => 'Petugas Pengetikan',
            ],
            [
                'name' => 'Petugas Pajak',
                'email_prefix' => 'pajak',
                'password' => 'password',
                'roleName' => 'Petugas Pajak',
            ],
            [
                'name' => 'Petugas Penyiapan',
                'email_prefix' => 'petugas5',
                'password' => 'password',
                'roleName' => 'Petugas Penyiapan',
            ],
        ];

        foreach ($usersData as $userData) {
            // 1. Cari role berdasarkan nama
            $role = Role::where('name', $userData['roleName'])->first();

            // 2. Jika role ditemukan, buat user
            if ($role) {
                // --- PERUBAHAN 2: Gabungkan prefix dengan domain ---
                $email = $userData['email_prefix'] . '@' . $domain;

                // Gunakan firstOrCreate untuk menghindari duplikat
                User::firstOrCreate(
                    ['email' => $email], // Kondisi untuk mengecek
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
