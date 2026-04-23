<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            ['name' => 'Administrador', 'code' => 'admin', 'requires_username' => false, 'is_operator' => false, 'assignable_by' => 'superadmin', 'sort_order' => 1],
            ['name' => 'Gerente', 'code' => 'manager', 'requires_username' => false, 'is_operator' => false, 'assignable_by' => 'superadmin,admin', 'sort_order' => 2],
            ['name' => 'Operador', 'code' => 'operator', 'requires_username' => true, 'is_operator' => true, 'assignable_by' => 'superadmin,admin,manager', 'sort_order' => 3],
        ];

        foreach ($profiles as $p) {
            DB::table('profiles')->updateOrInsert(
                ['code' => $p['code']],
                array_merge($p, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
