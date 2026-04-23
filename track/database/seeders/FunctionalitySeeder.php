<?php

namespace Database\Seeders;

use App\Models\Functionality;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class FunctionalitySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Painel', 'slug' => 'dashboard', 'platform' => 'web', 'sort_order' => 1],
            ['name' => 'Departamentos', 'slug' => 'department', 'platform' => 'web', 'sort_order' => 2],
            ['name' => 'Gestão de Docas', 'slug' => 'dock.management', 'platform' => 'web', 'sort_order' => 3],
            ['name' => 'Painel de Docas', 'slug' => 'dock.panel', 'platform' => 'web', 'sort_order' => 4],
            ['name' => 'Docas Pendentes', 'slug' => 'devices.pending', 'platform' => 'web', 'sort_order' => 5],
            ['name' => 'Dispositivos', 'slug' => 'device', 'platform' => 'web', 'sort_order' => 6],
            ['name' => 'Mapa da Empresa', 'slug' => 'company-map', 'platform' => 'web', 'sort_order' => 7],
            ['name' => 'Usuários', 'slug' => 'user', 'platform' => 'web', 'sort_order' => 8],
            ['name' => 'Perfis', 'slug' => 'profiles', 'platform' => 'web', 'sort_order' => 9],
            ['name' => 'Permissões', 'slug' => 'permissions', 'platform' => 'web', 'sort_order' => 10],
            ['name' => 'Configuração', 'slug' => 'configuration', 'platform' => 'web', 'sort_order' => 11],
            ['name' => 'Registros de Atividade', 'slug' => 'logs', 'platform' => 'web', 'sort_order' => 12],
            ['name' => 'Notificações', 'slug' => 'notification', 'platform' => 'web', 'sort_order' => 13],
            ['name' => 'Checkout (App)', 'slug' => 'app.checkout', 'platform' => 'app', 'sort_order' => 20],
            ['name' => 'Configurar Doca (App)', 'slug' => 'app.setup-dock', 'platform' => 'app', 'sort_order' => 21],
            ['name' => 'Cadastro de Rostos (App)', 'slug' => 'app.face-register', 'platform' => 'app', 'sort_order' => 22],
            ['name' => 'Relatórios (App)', 'slug' => 'app.reports', 'platform' => 'app', 'sort_order' => 23],
        ];

        foreach ($items as $item) {
            Functionality::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }

        $managerProfile = Profile::where('code', 'manager')->first();
        $operatorProfile = Profile::where('code', 'operator')->first();

        if ($managerProfile) {
            $managerSlugs = [
                'dashboard', 'department', 'dock.management', 'dock.panel', 'device',
                'company-map', 'user', 'configuration', 'logs', 'notification',
                'app.checkout', 'app.setup-dock', 'app.face-register', 'app.reports',
            ];
            $funcs = Functionality::whereIn('slug', $managerSlugs)->pluck('id');
            $managerProfile->functionalities()->sync($funcs);
        }

        if ($operatorProfile) {
            $operatorSlugs = ['app.checkout', 'app.reports'];
            $funcs = Functionality::whereIn('slug', $operatorSlugs)->pluck('id');
            $operatorProfile->functionalities()->sync($funcs);
        }
    }
}
