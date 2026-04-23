<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Cria ou atualiza os superadmins.
     * admin@track.com | admin123
     * darlley@gmail.com | 11111111
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@track.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'plain_password' => 'admin123',
                'role' => 'superadmin',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'darlley@gmail.com'],
            [
                'name' => 'Darley',
                'password' => Hash::make('11111111'),
                'plain_password' => '11111111',
                'role' => 'superadmin',
                'status' => 'active',
            ]
        );
    }
}
