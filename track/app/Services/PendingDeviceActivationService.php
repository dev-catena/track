<?php

namespace App\Services;

use App\Models\PendingDevice;
use App\Models\MqttTopic;
use App\Models\Department;
use App\Models\Dock;
use App\Repositories\Interfaces\ActivityLogInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingDeviceActivationService
{
    public function __construct(
        protected ActivityLogInterface $activityLog
    ) {}

    /**
     * Ativa doca pendente: cria tópico MQTT e Dock. A doca aparece em Gestão de Docas.
     * (Não cria Device - tablets são cadastrados separadamente em Gestão de Dispositivos)
     */
    public function activate(PendingDevice $pending, int $organizationId, int $departmentId, int $userId): array
    {
        $department = Department::where('id', $departmentId)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $macForTopic = str_replace([':', '-'], '', strtolower($pending->mac_address));
            $topicName = "iot-{$macForTopic}";

            $topic = MqttTopic::updateOrCreate(
                ['name' => $topicName],
                [
                    'name' => $topicName,
                    'description' => "Tópico para {$pending->device_name}",
                    'is_active' => true,
                ]
            );

            $dock = Dock::create([
                'name' => $pending->device_name,
                'location' => $department->name,
                'department_id' => $department->id,
                'mqtt_topic_id' => $topic->id,
                'pairing_code' => self::generatePairingCode(),
                'capacity' => '1',
                'dock_number' => $macForTopic,
                'status' => 'active',
                'dock_status' => 'available',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->activityLog->create([
                'organization_id' => $organizationId,
                'department_id' => $department->id,
                'action' => 'CREATE',
                'entity' => 'Dock',
                'description' => 'Doca ativada a partir de pendente: ' . $pending->device_name,
                'ip_address' => request()->ip(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $pending->update([
                'status' => 'activated',
                'activated_at' => now(),
                'activated_by' => $userId,
                'mqtt_topic_id' => $topic->id,
            ]);

            DB::commit();

            return [
                'topic' => $topic,
                'dock' => $dock,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao ativar doca pendente', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function generatePairingCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (Dock::where('pairing_code', $code)->exists());
        return $code;
    }
}
