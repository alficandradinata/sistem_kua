<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * [SISTEM KUA] Akun demo tiap peran. Password semua: "password". Lihat PROGRESS.md.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrator KUA', 'email' => 'admin@kua.test',   'role' => User::ROLE_ADMIN,   'phone' => '081200000001'],
            ['name' => 'Petugas Loket',     'email' => 'petugas@kua.test', 'role' => User::ROLE_PETUGAS, 'phone' => '081200000002'],
            ['name' => 'Budi Warga',        'email' => 'warga@kua.test',   'role' => User::ROLE_WARGA,   'phone' => '081200000003'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );
        }
    }
}
