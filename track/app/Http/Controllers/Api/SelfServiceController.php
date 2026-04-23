<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dock;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SelfServiceController extends Controller
{
    /**
     * Lista docas do departamento para seleção no tablet.
     * O tablet exibe a lista; o usuário seleciona a doca pelo MAC (etiquetado na doca).
     *
     * GET /api/self-service/docks?department_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $departmentId = $request->input('department_id');
        $organizationId = $request->input('organization_id');

        if ($organizationId) {
            $request->validate(['organization_id' => 'required|integer|exists:organizations,id']);
            $query = Dock::with('mqttTopic')
                ->whereHas('department', fn ($q) => $q->where('organization_id', $organizationId));
        } elseif ($departmentId) {
            $request->validate(['department_id' => 'required|integer|exists:departments,id']);
            $query = Dock::with('mqttTopic')->where('department_id', $departmentId);
        } else {
            return response()->json(['success' => false, 'message' => 'Informe department_id ou organization_id.'], 422);
        }

        $docks = $query
            ->where('status', 'active')
            ->whereNotNull('mqtt_topic_id')
            ->orderBy('name')
            ->get()
            ->map(function (Dock $dock) {
                $mac = self::formatDockNumberAsMac($dock->dock_number);
                return [
                    'id' => $dock->id,
                    'name' => $dock->name,
                    'mac_address' => $mac,
                    'pairing_code' => $dock->pairing_code,
                    'location' => $dock->location,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $docks,
        ]);
    }

    /**
     * Abre a doca. Aceita pairing_code OU mac_address.
     * - pairing_code: código de 6 caracteres (ex: ABC123)
     * - mac_address: MAC da doca etiquetado na doca (ex: a1:b2:c3:d4:e5:f6)
     *
     * POST /api/self-service/open
     * Body: { "pairing_code": "ABC123" } OU { "mac_address": "a1:b2:c3:d4:e5:f6" }
     * Opcional: { "slot": 1-6 } - slot específico da doca (1 a 6)
     */
    public function open(Request $request): JsonResponse
    {
        $pairingCode = $request->input('pairing_code');
        $macAddress = $request->input('mac_address');

        if (!$pairingCode && !$macAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Informe pairing_code ou mac_address.',
            ], 422);
        }

        $dock = null;

        if ($pairingCode) {
            $code = strtoupper((string) $pairingCode);
            if (strlen($code) === 6) {
                $dock = Dock::with('mqttTopic')
                    ->where('pairing_code', $code)
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$dock && $macAddress) {
            $macNormalized = str_replace([':', '-', ' '], '', strtolower((string) $macAddress));
            if (strlen($macNormalized) === 12) {
                $dock = Dock::with('mqttTopic')
                    ->where('dock_number', $macNormalized)
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$dock) {
            return response()->json([
                'success' => false,
                'message' => 'Doca não encontrada ou identificador inválido.',
            ], 404);
        }

        if (!$dock->mqtt_topic_id || !$dock->mqttTopic) {
            return response()->json([
                'success' => false,
                'message' => 'Doca sem tópico MQTT configurado.',
            ], 400);
        }

        $extra = [];
        $slot = $request->input('slot');
        $topicName = $dock->mqttTopic->name;

        if ($slot !== null && $slot >= 1 && $slot <= 6) {
            $extra['slot'] = (int) $slot;
            $command = 'open';
        } else {
            // Checkout pelo app (reconhecimento facial + botão): enviar slot_status para ESP32
            // ler sensores, escolher slot livre no backend e acender LED
            $fcmToken = $request->input('fcm_token');
            $operatorId = $request->input('operator_id');
            if ($fcmToken || $operatorId) {
                Cache::put('dock_open_pending_' . $topicName, [
                    'fcm_token' => $fcmToken,
                    'operator_id' => $operatorId,
                    'expires_at' => now()->addSeconds(30),
                ], 30);
            }
            $command = 'slot_status';
        }

        $mqtt = app(MqttService::class);
        if (!$mqtt->sendCommand($topicName, $command, $extra)) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao enviar comando.',
            ], 500);
        }

        $message = $command === 'slot_status'
            ? 'Pedido enviado à doca (leitura de slots / LED).'
            : 'Comando de abertura enviado.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'dock_name' => $dock->name,
                'mac_address' => self::formatDockNumberAsMac($dock->dock_number),
                'mqtt_command' => $command,
            ],
        ]);
    }

    /**
     * Fecha a doca (checkin/devolução).
     * POST /api/self-service/close
     * Body: { "pairing_code": "ABC123" } OU { "mac_address": "a1:b2:c3:d4:e5:f6" }
     */
    public function close(Request $request): JsonResponse
    {
        $pairingCode = $request->input('pairing_code');
        $macAddress = $request->input('mac_address');

        if (!$pairingCode && !$macAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Informe pairing_code ou mac_address.',
            ], 422);
        }

        $dock = null;
        if ($pairingCode) {
            $code = strtoupper((string) $pairingCode);
            if (strlen($code) === 6) {
                $dock = Dock::with('mqttTopic')
                    ->where('pairing_code', $code)
                    ->where('status', 'active')
                    ->first();
            }
        }
        if (!$dock && $macAddress) {
            $macNormalized = str_replace([':', '-', ' '], '', strtolower((string) $macAddress));
            if (strlen($macNormalized) === 12) {
                $dock = Dock::with('mqttTopic')
                    ->where('dock_number', $macNormalized)
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$dock || !$dock->mqtt_topic_id || !$dock->mqttTopic) {
            return response()->json([
                'success' => false,
                'message' => 'Doca não encontrada ou sem tópico MQTT.',
            ], 404);
        }

        $extra = [];
        $slot = $request->input('slot');
        if ($slot !== null && $slot >= 1 && $slot <= 6) {
            $extra['slot'] = (int) $slot;
        }

        $mqtt = app(MqttService::class);
        if (!$mqtt->sendCommand($dock->mqttTopic->name, 'close', $extra)) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao enviar comando.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Doca fechada.',
        ]);
    }

    /**
     * Formata dock_number (ex: a1b2c3d4e5f6) como MAC (ex: a1:b2:c3:d4:e5:f6).
     */
    private static function formatDockNumberAsMac(?string $dockNumber): ?string
    {
        if (!$dockNumber || strlen($dockNumber) !== 12) {
            return null;
        }
        $clean = str_replace([':', '-', ' '], '', strtolower($dockNumber));
        if (strlen($clean) !== 12) {
            return null;
        }
        return implode(':', str_split($clean, 2));
    }
}
