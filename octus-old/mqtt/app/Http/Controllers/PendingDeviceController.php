<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\PendingDevice;
use App\Models\Topic;

class PendingDeviceController extends Controller
{
    /**
     * Listar dispositivos pendentes
     */
    public function index(): JsonResponse
    {
        try {
            $devices = PendingDevice::orderBy('registered_at', 'desc')->get();

            $stats = [
                'total' => $devices->count(),
                'pending' => $devices->where('status', 'pending')->count(),
                'activated' => $devices->where('status', 'activated')->count(),
                'rejected' => $devices->where('status', 'rejected')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $devices,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar dispositivos pendentes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Registrar novo dispositivo ESP32
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('ESP32 Device Registration Start', ['request_data' => $request->all()]);
            
            $validatedData = $request->validate([
                'mac_address' => 'required|string|max:17',
                'device_name' => 'required|string|max:255',
                'ip_address' => 'nullable|ip',
                'wifi_ssid' => 'nullable|string|max:255',
                'device_info' => 'nullable',
                'registered_at' => 'nullable|integer'
            ]);

            // Verificar se já existe
            $existingDevice = PendingDevice::where('mac_address', $validatedData['mac_address'])->first();
            
            if ($existingDevice) {
                if ($existingDevice->status === 'pending') {
                    $updateData = [
                        'ip_address' => $validatedData['ip_address'] ?? $existingDevice->ip_address,
                        'wifi_ssid' => $validatedData['wifi_ssid'] ?? $existingDevice->wifi_ssid,
                        'registered_at' => $validatedData['registered_at'] ?? time() * 1000
                    ];
                    
                    if (!empty($validatedData['device_name'])) {
                        $updateData['device_name'] = $validatedData['device_name'];
                    }
                    
                    if (isset($validatedData['device_info'])) {
                        $updateData['device_info'] = $validatedData['device_info'];
                    }
                    
                    $existingDevice->update($updateData);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Dispositivo atualizado com sucesso',
                        'data' => $existingDevice->fresh()
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dispositivo já registrado com status: ' . $existingDevice->status
                    ], 409);
                }
            }

            // Criar novo dispositivo
            $device = PendingDevice::create([
                'mac_address' => $validatedData['mac_address'],
                'device_name' => $validatedData['device_name'],
                'ip_address' => $validatedData['ip_address'],
                'wifi_ssid' => $validatedData['wifi_ssid'],
                'device_info' => $validatedData['device_info'],
                'status' => 'pending',
                'registered_at' => $validatedData['registered_at'] ?? time() * 1000
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Dispositivo registrado com sucesso',
                'data' => $device
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar dispositivo: ' . $e->getMessage(), [
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Mostrar dispositivo específico
     */
    public function show($id): JsonResponse
    {
        try {
            $device = PendingDevice::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $device
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo não encontrado'
            ], 404);
        }
    }

    /**
     * Ativar dispositivo pendente (VERSÃO BÁSICA FUNCIONAL)
     */
    public function activate(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'device_type' => 'required|integer',
                'department' => 'required|integer'
            ]);

            $device = PendingDevice::findOrFail($id);
            
            if ($device->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Dispositivo não pode ser ativado. Status atual: ' . $device->status
                ], 400);
            }

            // Criar tópico MQTT simples (formato compatível com firmware: iot-mac)
            $macForTopic = str_replace(':', '', strtolower($device->mac_address));
            $topicName = "iot-{$macForTopic}";
            
            // Criar tópico básico
            $topic = Topic::updateOrCreate(["name" => $topicName], [
                'name' => $topicName,
                'description' => "Tópico para {$device->device_name} - Tipo: {$validatedData['device_type']} - Dept: {$validatedData['department']}",
                'is_active' => true
            ]);

            // Atualizar dispositivo
            $device->update([
                'status' => 'activated',
                'activated_at' => now(),
                'activated_by' => 1
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '🎉 Tópico MQTT criado com sucesso! Aguardando dispositivo para receber configuração.',
                'data' => [
                    'topic_name' => $topicName,
                    'device_name' => $device->device_name,
                    'device_type' => $validatedData['device_type'],
                    'department' => $validatedData['department'],
                    'mac_address' => $device->mac_address,
                    'mqtt_config_sent' => false,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erro ao ativar dispositivo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Excluir dispositivo
     */
    public function destroy($id): JsonResponse
    {
        try {
            $device = PendingDevice::findOrFail($id);
            $device->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Dispositivo excluído com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
} 