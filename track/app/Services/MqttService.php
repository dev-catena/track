<?php

namespace App\Services;

use App\Models\MqttTopic;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    /**
     * Envia comando MQTT para o tópico (formato: {topic}/cmd).
     * Payload: {"command":"open"|"close", "slot": 1-6 (opcional)}
     *
     * @param  array<string, mixed>  $extra  Parâmetros extras (ex: ['slot' => 3])
     */
    public function sendCommand(string $topicName, string $command, array $extra = []): bool
    {
        $topic = MqttTopic::where('name', $topicName)->where('is_active', true)->first();

        if (!$topic) {
            Log::warning('MqttService: Tópico não encontrado ou inativo', ['topic' => $topicName]);
            return false;
        }

        try {
            $client = new MqttClient(
                config('mqtt.host'),
                config('mqtt.port'),
                config('mqtt.client_id') . '_' . time()
            );

            $client->connect();

            $payload = array_merge(['command' => $command], $extra);
            $payload = json_encode($payload);
            $commandTopic = $topicName . '/cmd';

            $client->publish($commandTopic, $payload, 0);
            $client->disconnect();

            Log::info('MqttService: Comando enviado', [
                'topic' => $commandTopic,
                'payload' => $payload,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('MqttService: Erro ao enviar comando', [
                'topic' => $topicName,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envia get_slots para a doca. A doca responde em {topic}/feedback com available_slots.
     * Útil para handshake: quem chama deve estar inscrito em {topic}/feedback para receber.
     */
    public function sendGetSlots(string $topicName, ?string $requestId = null): bool
    {
        $extra = ['request_id' => $requestId ?? uniqid('slots_', true)];
        return $this->sendCommand($topicName, 'get_slots', $extra);
    }

    /**
     * Publica payload OTA no tópico {topicName}/ota.
     * Payload: firmware_url, version, ota_id, force_update, checksum_md5 (opcional)
     */
    public function publishOta(string $topicName, array $payload): bool
    {
        $topic = MqttTopic::where('name', $topicName)->where('is_active', true)->first();

        if (!$topic) {
            Log::warning('MqttService: Tópico não encontrado ou inativo', ['topic' => $topicName]);
            return false;
        }

        try {
            $client = new MqttClient(
                config('mqtt.host'),
                config('mqtt.port'),
                config('mqtt.client_id') . '_ota_' . time()
            );

            $client->connect();

            $otaTopic = $topicName . '/ota';
            $json = json_encode($payload);
            $client->publish($otaTopic, $json, 0);
            $client->disconnect();

            Log::info('MqttService: OTA publicado', ['topic' => $otaTopic, 'version' => $payload['version'] ?? '?']);

            return true;
        } catch (\Exception $e) {
            Log::error('MqttService: Erro ao publicar OTA', [
                'topic' => $topicName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
