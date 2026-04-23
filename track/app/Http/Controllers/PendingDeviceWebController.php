<?php

namespace App\Http\Controllers;

use App\Models\PendingDevice;
use App\Models\Department;
use App\Models\Organization;
use App\Services\PendingDeviceActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SessionController;

class PendingDeviceWebController extends Controller
{
    public function __construct(
        protected PendingDeviceActivationService $activationService
    ) {}

    /**
     * Listar dispositivos pendentes (apenas status=pending - ativados saem da lista)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PendingDevice::with('mqttTopic')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc');

        $departments = $this->getDepartmentsForUser($user);
        $organizations = $this->getOrganizationsForUser($user);
        $selectedOrganizationId = $user->role === 'superadmin'
            ? session(SessionController::SESSION_KEY)
            : null;

        if ($request->ajax()) {
            $devices = $query->get();
            return response()->json([
                'success' => true,
                'data' => $devices,
            ]);
        }

        $pending = $query->get();

        return view('common.pending-devices.index', compact('pending', 'departments', 'organizations', 'user', 'selectedOrganizationId'));
    }

    /**
     * Ativar dispositivo: cria tópico MQTT, Dock e Device. Dispositivo sai da lista pendente e vai para Gestão de Dispositivos.
     */
    public function activate(Request $request, int $id)
    {
        $user = Auth::user();

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
            return $this->sendJsonResponse(0, 'Dispositivo não pode ser ativado. Status: ' . $pending->status);
        }

        $departmentId = (int) $request->department;
        $organizationId = $user->role === 'superadmin'
            ? (int) $request->organization
            : $user->organization_id;

        try {
            $result = $this->activationService->activate($pending, $organizationId, $departmentId, $user->id);
            return $this->sendJsonResponse(1, 'Doca ativada. Agora aparece em Gestão de Docas.', [
                'topic_name' => $result['topic']->name,
                'dock_id' => $result['dock']->id,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendJsonResponse(0, 'Departamento não encontrado ou não pertence à empresa.');
        } catch (\Exception $e) {
            return $this->sendJsonResponse(0, 'Erro ao ativar: ' . $e->getMessage());
        }
    }

    /**
     * Reverter dispositivo ativado para pendente - para reaparecer em Docas Pendentes.
     * Útil quando a doca já foi ativada antes e você quer registrar/reatribuir novamente.
     */
    public function revertToPending(Request $request)
    {
        $request->validate(['mac_address' => 'required|string|max:17']);

        $macNorm = str_replace([':', '-', ' '], '', strtolower($request->mac_address));
        if (strlen($macNorm) !== 12) {
            return $this->sendJsonResponse(0, 'MAC inválido. Use formato: B0:CB:D8:8B:80:BC');
        }

        $pending = PendingDevice::whereRaw("REPLACE(REPLACE(REPLACE(LOWER(mac_address), ':', ''), '-', ''), ' ', '') = ?", [$macNorm])->first();

        if (!$pending) {
            return $this->sendJsonResponse(0, 'Dispositivo não encontrado.');
        }

        if ($pending->status !== 'activated') {
            return $this->sendJsonResponse(0, 'Dispositivo já está pendente.');
        }

        $dock = \App\Models\Dock::where('dock_number', $macNorm)->first();
        if ($dock) {
            \App\Models\Device::where('dock_id', $dock->id)->update(['dock_id' => null]);
            $dock->delete(); // soft delete
        }

        $pending->update([
            'status' => 'pending',
            'activated_at' => null,
            'activated_by' => null,
            'mqtt_topic_id' => null,
        ]);

        return $this->sendJsonResponse(1, 'Doca revertida para pendente. Na próxima conexão do ESP32, aparecerá aqui para ativar.');
    }

    private function getDepartmentsForUser($user)
    {
        if ($user->role === 'admin') {
            return Department::forSelectHierarchical((int) $user->organization_id)
                ->map(fn (array $row) => (object) $row);
        }
        if ($user->role === 'manager' && $user->department_id) {
            $d = Department::query()
                ->where('id', $user->department_id)
                ->where('status', 'active')
                ->first(['id', 'name']);
            if ($d) {
                return collect([(object) ['id' => $d->id, 'name' => $d->name, 'depth' => 0]]);
            }
        }
        if ($user->role === 'superadmin') {
            return collect();
        }
        return collect();
    }

    private function getOrganizationsForUser($user)
    {
        if ($user->role !== 'superadmin') {
            return collect();
        }
        return Organization::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
