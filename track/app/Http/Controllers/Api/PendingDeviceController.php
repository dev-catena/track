<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingDevice;
use App\Services\PendingDeviceActivationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PendingDeviceController extends Controller
{
    /**
     * Listar dispositivos pendentes
     */
    public function index(): JsonResponse
    {
        $devices = PendingDevice::with('mqttTopic')->orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * Registro de doca/ESP32 (público - sem auth). O ESP32 é a doca.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'device_name' => 'required|string|max:255',
                'ip_address' => 'nullable|string',
                'wifi_ssid' => 'nullable|string|max:255',
                'device_info' => 'nullable',
                'firmware_version' => 'nullable|string|max:50',
                'registered_at' => 'nullable|integer',
            ]);

            // Normalizar device_info: firmware envia string, model espera array
            $deviceInfo = $validated['device_info'] ?? null;
            if (is_string($deviceInfo)) {
                $deviceInfo = ['info' => $deviceInfo];
            }

            $existing = PendingDevice::where('mac_address', $validated['mac_address'])->first();

            if ($existing) {
                if ($existing->status === 'pending') {
                    $existing->update([
                        'ip_address' => $validated['ip_address'] ?? $existing->ip_address,
                        'wifi_ssid' => $validated['wifi_ssid'] ?? $existing->wifi_ssid,
                        'device_name' => $validated['device_name'] ?? $existing->device_name,
                        'device_info' => $deviceInfo ?? $existing->device_info,
                        'firmware_version' => $validated['firmware_version'] ?? $existing->firmware_version,
                        'firmware_updated_at' => isset($validated['firmware_version']) ? now() : $existing->firmware_updated_at,
                        'last_seen_at' => now(),
                        'registered_at' => $validated['registered_at'] ?? $existing->registered_at,
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Doca atualizada',
                        'data' => $existing->fresh(),
                    ]);
                }
                // Já ativada na fábrica: aceita check-in (atualiza IP/WiFi/firmware) - não reaparece em Docas Pendentes
                $existing->update([
                    'ip_address' => $validated['ip_address'] ?? $existing->ip_address,
                    'wifi_ssid' => $validated['wifi_ssid'] ?? $existing->wifi_ssid,
                    'firmware_version' => $validated['firmware_version'] ?? $existing->firmware_version,
                    'firmware_updated_at' => isset($validated['firmware_version']) ? now() : $existing->firmware_updated_at,
                    'last_seen_at' => now(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Doca online',
                    'deployed' => true,
                    'data' => $existing->fresh(),
                ]);
            }

            $device = PendingDevice::create([
                'mac_address' => $validated['mac_address'],
                'device_name' => $validated['device_name'],
                'ip_address' => $validated['ip_address'] ?? null,
                'wifi_ssid' => $validated['wifi_ssid'] ?? null,
                'device_info' => $deviceInfo,
                'firmware_version' => $validated['firmware_version'] ?? null,
                'firmware_updated_at' => isset($validated['firmware_version']) ? now() : null,
                'last_seen_at' => now(),
                'status' => 'pending',
                'registered_at' => $validated['registered_at'] ?? (int) (microtime(true) * 1000),
            ]);

            Log::info('Doca pendente registrada', ['id' => $device->id, 'mac' => $device->mac_address]);

            return response()->json([
                'success' => true,
                'message' => 'Doca registrada com sucesso',
                'data' => $device,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Registro de doca - validação falhou', ['errors' => $e->errors(), 'payload' => $request->except(['device_info'])]);
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar doca', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * Check-in de doca já ativada (só atualiza rede - SSID, senha, IP).
     * Usado no cliente quando a doca precisa ser reconfigurada para a rede local.
     * NÃO cria registro novo e NÃO aparece em Docas Pendentes.
     *
     * POST /api/devices/checkin
     * Body: mac_address, ip_address, wifi_ssid, firmware_version (opcional)
     */
    public function checkin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mac_address' => 'required|string|max:17',
                'ip_address' => 'nullable|string',
                'wifi_ssid' => 'nullable|string|max:255',
                'firmware_version' => 'nullable|string|max:50',
            ]);

            $existing = PendingDevice::where('mac_address', $validated['mac_address'])
                ->where('status', 'activated')
                ->first();

            if (!$existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doca não encontrada ou ainda não ativada. Use o registro normal.',
                ], 404);
            }

            $existing->update([
                'ip_address' => $validated['ip_address'] ?? $existing->ip_address,
                'wifi_ssid' => $validated['wifi_ssid'] ?? $existing->wifi_ssid,
                'firmware_version' => $validated['firmware_version'] ?? $existing->firmware_version,
                'firmware_updated_at' => isset($validated['firmware_version']) ? now() : $existing->firmware_updated_at,
                'last_seen_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rede atualizada',
                'deployed' => true,
                'data' => $existing->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Ativar dispositivo pendente - cria tópico MQTT, Dock e Device (vai para Gestão de Dispositivos)
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $rules = [
            'device_type' => 'required|integer',
            'department' => 'required|integer',
        ];
        if ($user->role === 'superadmin') {
            $rules['organization'] = 'required|integer';
        }
        $request->validate($rules);

        $pending = PendingDevice::findOrFail($id);

        if ($pending->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo não pode ser ativado. Status: ' . $pending->status,
            ], 400);
        }

        $organizationId = $user->role === 'superadmin'
            ? (int) $request->organization
            : $user->organization_id;
        $departmentId = (int) $request->department;

        try {
            $service = app(PendingDeviceActivationService::class);
            $result = $service->activate($pending, $organizationId, $departmentId, $user->id);
            return response()->json([
                'success' => true,
                'message' => 'Doca ativada. Agora aparece em Gestão de Docas.',
                'data' => [
                    'topic_name' => $result['topic']->name,
                    'topic_id' => $result['topic']->id,
                    'dock_id' => $result['dock']->id,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Departamento não encontrado ou não pertence à empresa.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('API: Erro ao ativar dispositivo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
