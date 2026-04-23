<?php

namespace App\Services;

use App\Models\MqttTopic;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function clearLastError(): void
    {
        $this->lastError = null;
    }

    /**
     * Ajustes de conexão (incl. usuário/senha do .env, antes ignorados no connect()).
     */
    private function connectionSettings(): ConnectionSettings
    {
        $timeout = (int) config('mqtt.connect_timeout', 10);
        $s = (new ConnectionSettings)
            ->setConnectTimeout($timeout);

        $user = (string) config('mqtt.username', '');
        if ($user !== '') {
            $s = $s
                ->setUsername($user)
                ->setPassword((string) config('mqtt.password', ''));
        }

        return $s;
    }

    private function newClient(string $idSuffix): MqttClient
    {
        return new MqttClient(
            (string) config('mqtt.host'),
            (int) config('mqtt.port'),
            (string) config('mqtt.client_id') . $idSuffix
        );
    }

    /**
     * Envia comando MQTT para o tópico (formato: {topic}/cmd).
     * Payload: {"command":"open"|"close", "slot": 1-6 (opcional)}
     *
     * @param  array<string, mixed>  $extra  Parâmetros extras (ex: ['slot' => 3])
     */
    public function sendCommand(string $topicName, string $command, array $extra = []): bool
    {
        $this->clearLastError();

        $topic = MqttTopic::where('name', $topicName)->where('is_active', true)->first();

        if (! $topic) {
            $this->lastError = 'Tópico MQTT não cadastrado ou inativo: '.$topicName;
            Log::warning('MqttService: Tópico não encontrado ou inativo', ['topic' => $topicName]);

            return false;
        }

        try {
            $client = $this->newClient('_'.time());
            $client->connect($this->connectionSettings(), false);

            $payload = array_merge(['command' => $command], $extra);
            $payload = json_encode($payload);
            $commandTopic = $topicName.'/cmd';

            $client->publish($commandTopic, $payload, 0);
            $client->disconnect();

            Log::info('MqttService: Comando enviado', [
                'topic' => $commandTopic,
                'payload' => $payload,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
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
        $this->clearLastError();

        $topic = MqttTopic::where('name', $topicName)->where('is_active', true)->first();

        if (! $topic) {
            $this->lastError = 'Tópico MQTT não cadastrado ou inativo: '.$topicName;
            Log::warning('MqttService: Tópico não encontrado ou inativo', ['topic' => $topicName]);

            return false;
        }

        try {
            $client = $this->newClient('_ota_'.time());
            $client->connect($this->connectionSettings(), false);

            $otaTopic = $topicName.'/ota';
            $json = json_encode($payload);
            $client->publish($otaTopic, $json, 0);
            $client->disconnect();

            Log::info('MqttService: OTA publicado', ['topic' => $otaTopic, 'version' => $payload['version'] ?? '?']);

            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('MqttService: Erro ao publicar OTA', [
                'topic' => $topicName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
