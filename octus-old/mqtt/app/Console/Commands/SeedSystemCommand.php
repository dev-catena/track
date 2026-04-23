<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:seed {--fresh : Drop all tables and recreate them before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the complete MQTT IoT system with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Sistema MQTT IoT - Seeding Completo');
        $this->newLine();

        // Verificar se deve fazer fresh migrate
        if ($this->option('fresh')) {
            $this->warn('⚠️  Atenção: Isso irá DELETAR todos os dados existentes!');
            if (!$this->confirm('Tem certeza que deseja continuar?')) {
                $this->error('❌ Operação cancelada pelo usuário.');
                return 1;
            }

            $this->info('🗑️  Removendo todas as tabelas e recriando...');
            Artisan::call('migrate:fresh');
            $this->info(Artisan::output());
        }

        $this->info('📊 Populando o sistema com dados de exemplo...');
        $this->newLine();

        // Executar o seeding
        Artisan::call('db:seed');
        $this->info(Artisan::output());

        $this->newLine();
        $this->info('✅ Seeding completo finalizado!');
        
        $this->displayQuickAccess();

        return 0;
    }

    private function displayQuickAccess()
    {
        $this->newLine();
        $this->info('🚀 GUIA RÁPIDO DE ACESSO:');
        $this->info('═══════════════════════');
        $this->newLine();

        $this->info('🔐 LOGIN PRINCIPAL:');
        $this->line('   Email: admin@sistema.com');
        $this->line('   Senha: admin123');
        $this->newLine();

        $this->info('🌐 APLICAÇÕES:');
        $this->line('   📊 Dashboard Web: http://10.102.0.103:8001');
        $this->line('   📱 App Config:    http://10.102.0.103:8002');
        $this->line('   🔧 API Backend:   http://10.102.0.103:8000/api');
        $this->newLine();

        $this->info('📋 DADOS CRIADOS:');
        $companies = \App\Models\Company::count();
        $departments = \App\Models\Department::count();
        $deviceTypes = \App\Models\DeviceType::count();
        $users = \App\Models\User::count();
        $topics = \App\Models\Topic::count();
        
        $this->line("   🏢 {$companies} Empresas");
        $this->line("   🏗️  {$departments} Departamentos");
        $this->line("   📱 {$deviceTypes} Tipos de Dispositivos");
        $this->line("   👥 {$users} Usuários");
        $this->line("   📡 {$topics} Tópicos MQTT");
        $this->newLine();

        $this->info('🎯 PRÓXIMOS PASSOS:');
        $this->line('   1. Acesse o dashboard web para gerenciar o sistema');
        $this->line('   2. Use o app config para registrar novos dispositivos');
        $this->line('   3. Monitore os tópicos MQTT em tempo real');
        $this->line('   4. Configure usuários e permissões conforme necessário');
        $this->newLine();

        $this->warn('💡 Dica: Use php artisan system:seed --fresh para resetar tudo');
    }
}
