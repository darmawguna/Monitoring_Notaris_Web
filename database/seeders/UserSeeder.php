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
                'name' => 'Komang Hendy',
                'email_prefix' => 'hendy.superadmin', // Gunakan prefix, bukan email lengkap
                'password' => 'password',
                'roleName' => 'Superadmin',
            ],
            [
                'name' => 'Ayu',
                'email_prefix' => 'ayu.entry',
                'password' => 'password',
                'roleName' => 'Petugas Entry',
            ],
            [
                'name' => 'IGA Intan Karisma',
                'email_prefix' => 'iga.intan',
                'password' => 'password',
                'roleName' => 'Petugas Pengetikan',
            ],
            [
                'name' => 'Jesika Elsa',
                'email_prefix' => 'jesika.elsa',
                'password' => 'password',
                'roleName' => 'Petugas Pengetikan',
            ],
            [
                'name' => 'Made Alit Handra',
                'email_prefix' => 'made.alit',
                'password' => 'password',
                'roleName' => 'Petugas Pajak',
            ],
            [
                'name' => 'Suci Tulasiani',
                'email_prefix' => 'suci',
                'password' => 'password',
                'roleName' => 'Petugas Penyiapan',
            ],
            [
                'name' => 'Ida Ayu Dita',
                'email_prefix' => 'dayu.dita',
                'password' => 'password',
                'roleName' => 'Petugas Penyiapan',
            ],
            [
                'name' => 'Ketut Arta Saputra',
                'email_prefix' => 'arta.saputra',
                'password' => 'password',
                'roleName' => 'Petugas BPN',
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
