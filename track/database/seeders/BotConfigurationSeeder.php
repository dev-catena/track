<?php

namespace Database\Seeders;

use App\Models\BotConfiguration;
use Illuminate\Database\Seeder;

class BotConfigurationSeeder extends Seeder
{
    /**
     * Configurações padrão para processos automáticos (checkout, lembretes, atraso, furto).
     * Escopo global (department_id null) - mesmas regras para todas as organizações.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'max_checkout_hours_device',
                'value' => '8',
                'type' => 'number',
                'category' => 'device',
                'description' => 'Horas máximas de checkout antes de considerar atraso. Mín: 1, Máx: 168 (1 semana).',
            ],
            [
                'key' => 'lost_device_timeout_days',
                'value' => '3',
                'type' => 'number',
                'category' => 'alert',
                'description' => 'Dias sem devolução para disparar alerta de furto. Mín: 1, Máx: 90.',
            ],
            [
                'key' => 'delay_retry_interval_minutes',
                'value' => '10',
                'type' => 'number',
                'category' => 'notification',
                'description' => 'Minutos entre retentativas da notificação de atraso. Mín: 5, Máx: 60.',
            ],
            [
                'key' => 'reminder_percentages',
                'value' => '90,99',
                'type' => 'string',
                'category' => 'notification',
                'description' => 'Percentuais do prazo para enviar lembrete (ex: 90,99 = 90% e 99%). Valores entre 50-99.',
            ],
        ];

        foreach ($configs as $config) {
            BotConfiguration::updateOrCreate(
                ['key' => $config['key']],
                array_merge($config, ['department_id' => null])
            );
        }
    }
}
