<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MqttService;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DockSlotStatusController extends Controller
{
    /**
     * Recebe o status dos slots do ESP32.
     * Salva no banco (dock_slot_status). Se houver open pendente, encontra slot livre e envia MQTT.
     *
     * POST /api/docks/slot-status
     * Body: { "id_doca": "iot-xxx", "ultima_atualizacao": "...", "slots": [{"id_slot":0,"status":"fechado","nivel_bateria":100}, ...] }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_doca' => 'required|string',
            'ultima_atualizacao' => 'nullable|string',
            'slots' => 'required|array',
            'slots.*' => 'array',
            'slots.*.id_slot' => 'required|integer|min:0|max:5',
            'slots.*.status' => 'required|string|in:aberto,fechado',
            'slots.*.nivel_bateria' => 'nullable|integer|min:0|max:100',
        ]);

        $topicName = $request->input('id_doca');
        $ultimaAtualizacao = $request->input('ultima_atualizacao');
        $slots = $request->input('slots');

        // Salvar no banco (teste isolado)
        try {
            DB::table('dock_slot_status')->insert([
                'id_doca' => $topicName,
                'ultima_atualizacao' => $ultimaAtualizacao ? \Carbon\Carbon::parse($ultimaAtualizacao) : now(),
                'slots' => json_encode($slots),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('DockSlotStatus: falha ao salvar no banco', ['error' => $e->getMessage()]);
        }

        $pending = Cache::get('dock_open_pending_' . $topicName);

        // Só aciona open / no_slots após checkout no app (POST /api/self-service/open com operator_id ou fcm_token).
        // Telemetria periódica do ESP sem pedido pendente só grava status, sem LED.
        if (! $pending) {
            return response()->json([
                'success' => true,
                'message' => 'Status registrado (sem checkout pendente).',
                'idle' => true,
            ]);
        }

        $mqtt = app(MqttService::class);
        $firstFreeSlot = null;
        foreach ($slots as $i => $s) {
            if (($s['status'] ?? '') === 'aberto') {
                $firstFreeSlot = ((int) ($s['id_slot'] ?? $i)) + 1;
                break;
            }
        }

        if ($firstFreeSlot !== null) {
            $mqtt->sendCommand($topicName, 'open', ['slot' => $firstFreeSlot]);
            Log::info('DockSlotStatus: slot livre encontrado, open enviado', [
                'topic' => $topicName,
                'slot' => $firstFreeSlot,
            ]);
            Cache::forget('dock_open_pending_' . $topicName);

            return response()->json([
                'success' => true,
                'message' => 'Slot aberto.',
                'slot' => $firstFreeSlot,
            ]);
        }

        $mqtt->sendCommand($topicName, 'no_slots', []);
        Log::warning('DockSlotStatus: nenhum slot livre', ['topic' => $topicName]);

        if (! empty($pending['fcm_token'])) {
            try {
                $fcm = new FCMService(app('log'));
                $fcm->sendToToken(
                    $pending['fcm_token'],
                    'Doca sem equipamento',
                    'Não há equipamento disponível na doca. Todos os slots estão ocupados.',
                    ['event' => 'dock_no_slots', 'topic' => $topicName]
                );
            } catch (\Throwable $e) {
                Log::error('DockSlotStatus: falha FCM', ['error' => $e->getMessage()]);
            }
        }
        Cache::forget('dock_open_pending_' . $topicName);

        return response()->json([
            'success' => false,
            'message' => 'Nenhum slot livre.',
        ]);
    }
}
