<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Department;
use App\Models\Dock;
use App\Models\PendingDevice;
use App\Models\AuditHistory;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\SessionController;

class CompanyMapController extends Controller
{
    /**
     * Exibe o mapa/grafo da empresa (Org → Dept → Docas).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $organizations = $this->getOrganizationsForUser($user);
        $selectedOrganizationId = $user->role === 'superadmin'
            ? (session(SessionController::SESSION_KEY) ?? $organizations->first()?->id)
            : $user->organization_id;
        return view('common.company-map.index', compact('user', 'organizations', 'selectedOrganizationId'));
    }

    /**
     * API: dados do grafo para vis-network.
     * GET /company-map/data?organization_id=1
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $orgId = $request->organization_id;

        if (!$orgId) {
            return response()->json(['success' => false, 'message' => 'organization_id obrigatório'], 422);
        }

        $query = Organization::with([
            'departments' => fn ($q) => $q->where('status', 'active'),
            'departments.docks' => fn ($q) => $q->with('mqttTopic')->whereNotNull('mqtt_topic_id'),
        ])->where('id', $orgId);

        if ($user->role !== 'superadmin') {
            $orgIdFilter = $user->organization_id;

            if ($user->role === 'manager' && $user->department_id) {
                $dept = \App\Models\Department::find($user->department_id);
                if ($dept) {
                    $orgIdFilter = $dept->organization_id;
                }
            }
            if ($orgIdFilter) {
                $query->where('id', $orgIdFilter);
            }
        }

        $org = $query->first();
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 404);
        }

        $nodes = [];
        $edges = [];
        $dockIds = [];

        // Nó raiz: Empresa
        $nodes[] = [
            'id' => 'org-' . $org->id,
            'label' => $org->name,
            'title' => "Empresa: {$org->name}",
            'group' => 'organization',
            'level' => 0,
        ];

        foreach ($org->departments()->where('status', 'active')->get() as $dept) {
            $nodes[] = [
                'id' => 'dept-' . $dept->id,
                'label' => $dept->name,
                'title' => "Departamento: {$dept->name}\nLocal: " . ($dept->location ?? '-'),
                'group' => 'department',
                'level' => 1,
            ];
            $edges[] = ['from' => 'org-' . $org->id, 'to' => 'dept-' . $dept->id];

            foreach ($dept->docks as $dock) {
                $dockIds[] = $dock->id;
            }
        }

        // Último checkout por dock (Device.dock_id → AuditHistory)
        $lastCheckouts = collect();
        if (!empty($dockIds)) {
            $lastCheckouts = AuditHistory::where('audit_type', 'check_out')
                ->whereHas('device', fn ($q) => $q->whereIn('dock_id', $dockIds))
                ->with('device:id,dock_id')
                ->orderBy('audit_out_time', 'desc')
                ->get()
                ->groupBy(fn ($a) => $a->device->dock_id)
                ->map(fn ($items) => $items->first());
        }

        // PendingDevice por dock_number (MAC) para IP, firmware, last_seen
        $pendingByMac = PendingDevice::where('status', 'activated')
            ->get()
            ->keyBy(fn ($p) => str_replace([':', '-'], '', strtolower($p->mac_address)));

        $oneHourAgo = Carbon::now()->subHour();
        $twentyFourHoursAgo = Carbon::now()->subHours(24);

        $departments = $org->departments()->where('status', 'active')->with('docks.mqttTopic')->get();
        foreach ($departments as $dept) {
            foreach ($dept->docks as $dock) {
                if (!$dock->mqtt_topic_id) {
                    continue;
                }

                $dockMacNorm = str_replace([':', '-', ' '], '', strtolower($dock->dock_number ?? ''));
                $pending = $dockMacNorm ? $pendingByMac->get($dockMacNorm) : null;
                $ip = $pending?->ip_address;
                $firmwareVersion = $pending?->firmware_version;
                $firmwareUpdatedAt = $pending?->firmware_updated_at;
                $lastSeenAt = $pending?->last_seen_at;

                // Verde: check-in na última hora | Laranja: ativo mas sem check-in na última hora | Vermelho: offline
                $connectionGroup = 'dock_offline';
                $connectionLabel = '🔴 Offline';
                if ($lastSeenAt) {
                    if ($lastSeenAt->gte($oneHourAgo)) {
                        $connectionGroup = 'dock_online';
                        $connectionLabel = '🟢 Online';
                    } elseif ($lastSeenAt->gte($twentyFourHoursAgo)) {
                        $connectionGroup = 'dock_idle';
                        $connectionLabel = '🟠 Sem check-in na última hora';
                    }
                }

                $lastCheckout = $lastCheckouts->get($dock->id);
                $lastCheckoutDt = $lastCheckout?->audit_out_time;

                $macFormatted = self::formatMacForDisplay($dock->dock_number ?? $pending?->mac_address ?? '');
                $dockStatusLabel = match (strtolower($dock->status ?? '')) {
                    'active' => 'Ativa',
                    'inactive' => 'Desativada',
                    'maintenance' => 'Manutenção',
                    default => ($dock->status ?? 'N/A'),
                };
                $dockTitle = "Doca: {$dock->name}\n";
                $dockTitle .= "Status doca: {$dockStatusLabel}\n";
                $dockTitle .= "Conexão: {$connectionLabel}\n";
                $dockTitle .= "IP: " . ($ip ?? '-') . "\n";
                $dockTitle .= "MAC: " . ($macFormatted ?: '-') . "\n";
                if (!$pending) {
                    $dockTitle .= "(Doca não vinculada ao dispositivo físico - confira o MAC/cadastro)\n";
                } elseif ($connectionGroup === 'dock_idle') {
                    $dockTitle .= "(Check-in há mais de 1 hora)\n";
                } elseif ($connectionGroup === 'dock_offline') {
                    $dockTitle .= "(Sem check-in há mais de 24h ou ESP32 desconectado)\n";
                }
                $dockTitle .= "Último checkout: " . ($lastCheckoutDt ? Carbon::parse($lastCheckoutDt)->format('d/m/Y H:i') : '-') . "\n";
                $dockTitle .= "Firmware: " . ($firmwareVersion ?? '-') . "\n";
                $dockTitle .= "Firmware em: " . ($firmwareUpdatedAt ? $firmwareUpdatedAt->format('d/m/Y H:i') : '-');

                $nodes[] = [
                    'id' => 'dock-' . $dock->id,
                    'label' => $dock->name,
                    'title' => $dockTitle,
                    'group' => $connectionGroup,
                    'level' => 2,
                    'dock_id' => $dock->id,
                    'ip' => $ip,
                    'is_online' => $connectionGroup === 'dock_online',
                    'last_checkout' => $lastCheckoutDt ? Carbon::parse($lastCheckoutDt)->format('d/m/Y H:i') : null,
                    'firmware_version' => $firmwareVersion,
                    'firmware_updated_at' => $firmwareUpdatedAt?->format('d/m/Y H:i'),
                ];

                $edges[] = ['from' => 'dept-' . $dept->id, 'to' => 'dock-' . $dock->id];
            }
        }

        return response()->json([
            'success' => true,
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }

    /**
     * Ping na doca (tenta HTTP no IP do ESP32).
     * GET /company-map/ping/{dockId}
     */
    public function ping(int $dockId)
    {
        $dock = Dock::with('mqttTopic')->find($dockId);
        if (!$dock) {
            return response()->json(['success' => false, 'online' => false], 404);
        }

        $dockMacNorm = str_replace([':', '-', ' '], '', strtolower($dock->dock_number ?? ''));
        $pending = $dockMacNorm ? PendingDevice::where('status', 'activated')->get()
            ->first(fn ($p) => str_replace([':', '-'], '', strtolower($p->mac_address)) === $dockMacNorm) : null;

        if (!$pending || !$pending->ip_address) {
            return response()->json([
                'success' => true,
                'online' => false,
                'message' => 'IP não disponível',
            ]);
        }

        $ip = $pending->ip_address;
        $url = "http://{$ip}:5000/device-info";
        $online = false;

        try {
            $response = Http::timeout(3)->get($url);
            $online = $response->successful();
        } catch (\Exception $e) {
            // timeout ou conexão recusada
        }

        return response()->json([
            'success' => true,
            'online' => $online,
            'ip' => $ip,
        ]);
    }

    /**
     * Lista firmware disponíveis (arquivos .bin em storage/app/firmware).
     * GET /company-map/firmware/list
     */
    public function firmwareList()
    {
        $path = config('firmware.storage_path', storage_path('app/firmware'));

        if (!File::isDirectory($path)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $files = File::files($path);
        $list = [];

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'bin') {
                continue;
            }
            $filename = $file->getFilename();
            $version = $this->parseVersionFromFilename($filename);
            $list[] = [
                'filename' => $filename,
                'version' => $version,
                'size' => $file->getSize(),
                'modified_at' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i'),
            ];
        }

        usort($list, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * Upload de novo firmware (.bin) para storage/app/firmware.
     * POST /company-map/firmware/upload
     */
    public function firmwareUpload(Request $request)
    {
        $request->validate([
            'firmware' => 'required|file|max:3072',
        ], [
            'firmware.required' => 'Selecione um arquivo.',
            'firmware.file' => 'Arquivo inválido.',
            'firmware.max' => 'O arquivo deve ter no máximo 3MB.',
        ]);

        $file = $request->file('firmware');
        $filename = $file->getClientOriginalName();

        if (!preg_match('/\.bin$/i', $filename)) {
            return response()->json([
                'success' => false,
                'message' => 'O nome do arquivo deve terminar em .bin',
            ], 422);
        }

        $path = config('firmware.storage_path', storage_path('app/firmware'));
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        try {
            $file->move($path, $filename);
            Log::info('Firmware enviado', ['filename' => $filename]);
            return response()->json([
                'success' => true,
                'message' => "Firmware {$filename} enviado com sucesso. Já está disponível para OTA.",
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar firmware', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar arquivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conta docas da empresa que receberão OTA (para confirmação).
     * GET /company-map/ota/count?organization_id=1
     */
    public function otaCount(Request $request)
    {
        $user = Auth::user();
        $orgId = $request->organization_id;

        if (!$orgId) {
            return response()->json(['success' => false, 'message' => 'organization_id obrigatório'], 422);
        }

        $query = Organization::with(['departments.docks' => fn ($q) => $q->whereNotNull('mqtt_topic_id')])
            ->where('id', $orgId);

        if ($user->role !== 'superadmin') {
            $orgIdFilter = $user->organization_id;
            if ($user->role === 'manager' && $user->department_id) {
                $dept = Department::find($user->department_id);
                if ($dept) {
                    $orgIdFilter = $dept->organization_id;
                }
            }
            if ($orgIdFilter) {
                $query->where('id', $orgIdFilter);
            }
        }

        $org = $query->first();
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 404);
        }

        $count = $org->docks()->whereNotNull('mqtt_topic_id')->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Dispara OTA para todas as docas da empresa.
     * POST /company-map/ota/trigger
     * Body: organization_id, firmware_filename
     */
    public function otaTrigger(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|integer',
            'firmware_filename' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $orgId = (int) $request->organization_id;
        $filename = $request->firmware_filename;

        $path = config('firmware.storage_path', storage_path('app/firmware'));
        $filePath = $path . DIRECTORY_SEPARATOR . basename($filename);

        if (!File::exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Arquivo de firmware não encontrado'], 404);
        }

        $query = Organization::with(['departments.docks' => fn ($q) => $q->with('mqttTopic')->whereNotNull('mqtt_topic_id')])
            ->where('id', $orgId);

        if ($user->role !== 'superadmin') {
            $orgIdFilter = $user->organization_id;
            if ($user->role === 'manager' && $user->department_id) {
                $dept = Department::find($user->department_id);
                if ($dept) {
                    $orgIdFilter = $dept->organization_id;
                }
            }
            if ($orgIdFilter) {
                $query->where('id', $orgIdFilter);
            }
        }

        $org = $query->first();
        if (!$org) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 404);
        }

        $baseUrl = config('firmware.base_url');
        $firmwareUrl = $baseUrl . '/firmware/download/' . rawurlencode(basename($filename));
        $version = $this->parseVersionFromFilename($filename);
        $otaId = 'track_' . time();

        $payload = [
            'firmware_url' => $firmwareUrl,
            'version' => $version,
            'ota_id' => $otaId,
            'force_update' => true,
        ];

        $mqtt = app(MqttService::class);
        $sent = 0;
        $failed = 0;

        foreach ($org->departments as $dept) {
            foreach ($dept->docks as $dock) {
                if (!$dock->mqtt_topic_id || !$dock->mqttTopic) {
                    continue;
                }
                if ($mqtt->publishOta($dock->mqttTopic->name, $payload)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        Log::info('OTA disparado', [
            'org_id' => $orgId,
            'firmware' => $filename,
            'version' => $version,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        \App\Models\OtaActivity::create([
            'organization_id' => $orgId,
            'created_by' => $user->id,
            'firmware_filename' => basename($filename),
            'firmware_version' => $version,
            'ota_id' => $otaId,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => true,
            'message' => "OTA enviado para {$sent} dispositivo(s)." . ($failed > 0 ? " Falha em {$failed}." : ''),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    private static function formatMacForDisplay(?string $mac): string
    {
        if (!$mac) {
            return '';
        }
        $clean = str_replace([':', '-', ' '], '', strtolower($mac));
        if (strlen($clean) !== 12 || !ctype_xdigit($clean)) {
            return $mac;
        }
        return implode(':', str_split($clean, 2));
    }

    private function parseVersionFromFilename(string $filename): string
    {
        if (preg_match('/[\d]+\.[\d]+\.[\d]+/', $filename, $m)) {
            return $m[0];
        }
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    private function getOrganizationsForUser($user)
    {
        if ($user->role === 'superadmin') {
            return Organization::where('status', 'active')->orderBy('name')->get();
        }
        if ($user->role === 'manager' && $user->department_id) {
            $dept = \App\Models\Department::find($user->department_id);
            if ($dept) {
                return Organization::where('id', $dept->organization_id)->where('status', 'active')->get();
            }
        }
        return Organization::where('id', $user->organization_id)->where('status', 'active')->get();
    }
}
