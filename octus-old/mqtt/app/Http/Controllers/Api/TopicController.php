<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PhpMqtt\Client\MqttClient;

class TopicController extends Controller
{
    /**
     * Criar um novo tópico
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:topics,name',
            'description' => 'nullable|string',
            'group_id' => 'nullable|exists:device_groups,id'
        ]);

        $topic = Topic::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => true
        ]);

        // Se um grupo foi especificado, criar a associação
        if ($request->group_id) {
            \App\Models\DeviceGroupAssignment::create([
                'device_id' => $topic->id,
                'group_id' => $request->group_id,
                'is_active' => true
            ]);
        }

        // Carregar o tópico com informações do grupo
        $topic->load('groupAssignment.group');

        return response()->json([
            'success' => true,
            'message' => 'Tópico criado com sucesso',
            'data' => $topic
        ], 201);
    }

    /**
     * Enviar mensagem para um tópico MQTT
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'topico' => 'required|string',
            'mensagem' => 'required|string'
        ]);

        $topicName = $request->topico;
        $message = $request->mensagem;

        // Verificar se o tópico existe no banco de dados
        $topic = Topic::where('name', $topicName)->where('is_active', true)->first();

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Tópico não existe ou está inativo'
            ], 404);
        }

        try {
            // Criar cliente MQTT
            $client = new MqttClient(
                config('mqtt.host', env('MQTT_HOST', 'localhost')),
                config('mqtt.port', env('MQTT_PORT', 1883)),
                config('mqtt.client_id', env('MQTT_CLIENT_ID', 'laravel_mqtt_client'))
            );

            // Conectar ao broker MQTT
            $client->connect();

            // Publicar mensagem no tópico
            $client->publish($topicName, $message, 0);

            // Desconectar do broker
            $client->disconnect();

            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso para o tópico: ' . $topicName,
                'data' => [
                    'topic' => $topicName,
                    'message' => $message
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar mensagem MQTT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publicar comando MQTT (para interface web)
     */
    public function publishCommand(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string',
            'payload' => 'required|array'
        ]);

        $topicName = $request->topic;
        $payload = $request->payload;
        $jsonMessage = json_encode($payload);

        \Log::info('📤 Publicando comando MQTT via interface web', [
            'topic' => $topicName,
            'payload' => $payload
        ]);

        try {
            // Criar cliente MQTT
            $client = new MqttClient(
                config('mqtt.host', env('MQTT_HOST', 'localhost')),
                config('mqtt.port', env('MQTT_PORT', 1883)),
                config('mqtt.client_id', env('MQTT_CLIENT_ID', 'laravel_web_interface'))
            );

            // Conectar ao broker MQTT
            $client->connect();

            // Publicar comando JSON no tópico
            $client->publish($topicName, $jsonMessage, 0);

            // Desconectar do broker
            $client->disconnect();

            \Log::info('✅ Comando MQTT publicado com sucesso', [
                'topic' => $topicName,
                'message' => $jsonMessage
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comando enviado com sucesso',
                'data' => [
                    'topic' => $topicName,
                    'payload' => $payload,
                    'json_message' => $jsonMessage,
                    'timestamp' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('❌ Erro ao publicar comando MQTT', [
                'error' => $e->getMessage(),
                'topic' => $topicName,
                'payload' => $payload
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar comando MQTT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar comando direto via endpoint RESTful
     */
    public function sendDirectCommand(Request $request, $topic_path): JsonResponse
    {
        $request->validate([
            'msg' => 'required|string'
        ]);

        // Construir o nome completo do tópico
        $topicName = 'iot/' . $topic_path;
        $message = $request->msg;

        \Log::info('📤 Comando direto via endpoint RESTful', [
            'endpoint' => $topic_path,
            'topic' => $topicName,
            'message' => $message
        ]);

        // Verificar se o tópico existe no banco de dados
        $topic = Topic::where('name', $topicName)->where('is_active', true)->first();

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => "Tópico '{$topicName}' não existe ou está inativo",
                'endpoint' => "/api/mqtt/iot/{$topic_path}",
                'topic' => $topicName
            ], 404);
        }

        try {
            // Criar cliente MQTT
            $client = new MqttClient(
                config('mqtt.host', env('MQTT_HOST', 'localhost')),
                config('mqtt.port', env('MQTT_PORT', 1883)),
                config('mqtt.client_id', env('MQTT_CLIENT_ID', 'laravel_direct_command'))
            );

            // Conectar ao broker MQTT
            $client->connect();

            // Publicar comando direto no tópico
            $client->publish($topicName, $message, 0);

            // Desconectar do broker
            $client->disconnect();

            \Log::info('✅ Comando direto enviado com sucesso', [
                'topic' => $topicName,
                'message' => $message,
                'endpoint' => $topic_path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comando enviado com sucesso',
                'data' => [
                    'topic' => $topicName,
                    'message' => $message,
                    'endpoint' => "/api/mqtt/iot/{$topic_path}",
                    'timestamp' => now()->toISOString(),
                    'device_mac' => $this->extractMacFromTopic($topicName)
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('❌ Erro ao enviar comando direto', [
                'error' => $e->getMessage(),
                'topic' => $topicName,
                'message' => $message,
                'endpoint' => $topic_path
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar comando MQTT: ' . $e->getMessage(),
                'endpoint' => "/api/mqtt/iot/{$topic_path}",
                'topic' => $topicName
            ], 500);
        }
    }

    /**
     * Extrair MAC address do nome do tópico
     */
    private function extractMacFromTopic($topicName)
    {
        // Extrair MAC do padrão: iot/departamento/tipo/mac_address
        $parts = explode('/', $topicName);
        return end($parts) ?? 'unknown';
    }
    
    /**
     * 🚀 PROCESSAR AUTO-REGISTRO DE DISPOSITIVOS ESP32
     */
    public function processDeviceRegistration(Request $request)
    {
        try {
            \Log::info('📱 Processando auto-registro de dispositivo ESP32', $request->all());
            
            $request->validate([
                'device_mac' => 'required|string',
                'device_ip' => 'required|ip',
                'wifi_ssid' => 'required|string',
                'device_type' => 'required|string'
            ]);
            
            $macAddress = $request->device_mac;
            $deviceIp = $request->device_ip;
            $wifiSSID = $request->wifi_ssid;
            
            // Gerar nome e departamento baseado no MAC
            $cleanMac = str_replace([':', '-'], '', strtolower($macAddress));
            $deviceName = "ESP32-" . substr($cleanMac, -6);
            $department = "producao"; // Padrão para auto-registro
            $deviceType = "atuador"; // Padrão para ESP32
            
            // Criar tópico MQTT para o dispositivo
            $topicName = "iot/{$department}/{$deviceType}/{$cleanMac}";
            
            // Verificar se tópico já existe
            $existingTopic = Topic::where('name', $topicName)->first();
            
            if (!$existingTopic) {
                // Criar novo tópico
                $topic = Topic::create([
                    'name' => $topicName,
                    'description' => "Auto-registrado: {$deviceName} em {$wifiSSID}",
                    'is_active' => true
                ]);
                
                \Log::info('✅ Tópico criado automaticamente', [
                    'topic' => $topicName,
                    'device_mac' => $macAddress,
                    'device_ip' => $deviceIp
                ]);
                
                // Configurar o tópico no ESP32 via MQTT
                $this->configureDeviceTopic($macAddress, $topicName);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo registrado automaticamente',
                    'data' => [
                        'device_name' => $deviceName,
                        'device_mac' => $macAddress,
                        'device_ip' => $deviceIp,
                        'topic_name' => $topicName,
                        'wifi_ssid' => $wifiSSID,
                        'department' => $department,
                        'device_type' => $deviceType,
                        'registered_at' => now()->toISOString()
                    ]
                ]);
            } else {
                // Tópico já existe - apenas atualizar IP
                \Log::info('🔄 Dispositivo já registrado - atualizando informações', [
                    'topic' => $topicName,
                    'new_ip' => $deviceIp
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo já registrado - informações atualizadas',
                    'data' => [
                        'device_name' => $deviceName,
                        'device_mac' => $macAddress,
                        'device_ip' => $deviceIp,
                        'topic_name' => $topicName,
                        'status' => 'already_registered'
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('❌ Erro no auto-registro de dispositivo', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro no auto-registro: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Configurar tópico no ESP32 via MQTT
     */
    private function configureDeviceTopic($macAddress, $topicName)
    {
        try {
            // Criar cliente MQTT
            $client = new MqttClient(
                config('mqtt.host', env('MQTT_HOST', 'localhost')),
                config('mqtt.port', env('MQTT_PORT', 1883)),
                config('mqtt.client_id', env('MQTT_CLIENT_ID', 'laravel_config'))
            );
            
            // Conectar ao broker MQTT
            $client->connect();
            
            // Enviar configuração do tópico para o ESP32
            $configPayload = json_encode([
                'command' => 'configure_mqtt',
                'broker' => config('mqtt.host', 'localhost'),
                'port' => config('mqtt.port', 1883),
                'topic' => $topicName
            ]);
            
            // Publicar configuração no tópico temporário de registro
            $configTopic = "iot/temp/registration/{$macAddress}";
            $client->publish($configTopic, $configPayload, 0);
            
            // Desconectar do broker
            $client->disconnect();
            
            \Log::info('📡 Configuração enviada para ESP32', [
                'mac' => $macAddress,
                'topic' => $topicName,
                'config_topic' => $configTopic
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Erro ao configurar tópico no ESP32', [
                'error' => $e->getMessage(),
                'mac' => $macAddress,
                'topic' => $topicName
            ]);
        }
    }

    /**
     * Enviar comando MQTT via API REST
     */
    public function sendCommand(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'topic' => 'required|string',
                'command' => 'required|string'
            ]);

            $topic = $request->topic;
            $command = $request->command;
            
            // Verificar se o tópico existe
            $topicRecord = Topic::where('name', $topic)->first();
            if (!$topicRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tópico não encontrado'
                ], 404);
            }

            // Criar cliente MQTT
            $client = new MqttClient(
                config('mqtt.host', env('MQTT_HOST', 'localhost')),
                config('mqtt.port', env('MQTT_PORT', 1883)),
                'postman_api_' . time()
            );

            // Conectar ao broker MQTT
            $client->connect();

            // Preparar payload do comando
            $payload = json_encode(['command' => $command]);
            
            // Log do que está sendo enviado
            \Log::info('📤 Enviando via API MQTT', [
                'topic' => $topic,
                'command_topic' => $topic . '/cmd', 
                'command' => $command,
                'payload' => $payload
            ]);
            
            // Enviar comando para o tópico/cmd
            $commandTopic = $topic . '/cmd';
            $client->publish($commandTopic, $payload, 0);

            // Desconectar
            $client->disconnect();

            return response()->json([
                'success' => true,
                'message' => 'Comando enviado com sucesso',
                'data' => [
                    'topic' => $commandTopic,
                    'command' => $command,
                    'payload' => $payload,
                    'sent_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar comando: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos os tópicos
     */
    public function index(): JsonResponse
    {
        $topics = Topic::with('groupAssignment.group')
                      ->where('is_active', true)
                      ->get();

        return response()->json([
            'success' => true,
            'data' => $topics
        ], 200);
    }

    /**
     * Mostrar um tópico específico
     */
    public function show($id): JsonResponse
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Tópico não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $topic
        ], 200);
    }

    /**
     * Atualizar um tópico
     */
    public function update(Request $request, $id): JsonResponse
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Tópico não encontrado'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:topics,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $topic->update($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Tópico atualizado com sucesso',
            'data' => $topic
        ], 200);
    }

    /**
     * Desativar um tópico
     */
    public function deactivate($id): JsonResponse
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Tópico não encontrado'
            ], 404);
        }

        $topic->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Tópico desativado com sucesso'
        ], 200);
    }

    /**
     * Excluir um tópico permanentemente
     */
    public function destroy($id): JsonResponse
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return response()->json([
                'success' => false,
                'message' => 'Tópico não encontrado'
            ], 404);
        }

        // Salvar nome do tópico antes de excluir
        $topicName = $topic->name;

        // Excluir permanentemente
        $topic->delete();

        \Log::info('🗑️ Tópico excluído permanentemente', [
            'id' => $id,
            'name' => $topicName
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tópico '{$topicName}' excluído com sucesso",
            'data' => [
                'id' => $id,
                'name' => $topicName,
                'deleted_at' => now()->toISOString()
            ]
        ], 200);
    }
}
