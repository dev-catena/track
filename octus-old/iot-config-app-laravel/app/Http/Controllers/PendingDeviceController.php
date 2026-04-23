<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PendingDeviceController extends Controller
{
    private $backendUrl;

    public function __construct()
    {
        $this->backendUrl = config('app.api_base_url', 'http://localhost:8000/api');
    }

    public function index()
    {
        try {
            // Buscar dispositivos pendentes da API
            $response = Http::get("{$this->backendUrl}/devices/pending");
            
            if ($response->successful()) {
                $data = $response->json();
                return view('pending-devices.index', [
                    'devices' => $data['data'] ?? [],
                    'stats' => $data['stats'] ?? []
                ]);
            }
            
            return view('pending-devices.index', [
                'devices' => [],
                'stats' => [],
                'error' => 'Erro ao carregar dispositivos pendentes'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao carregar pending devices: ' . $e->getMessage());
            
            return view('pending-devices.index', [
                'devices' => [],
                'stats' => [],
                'error' => 'Erro de conexão com o backend'
            ]);
        }
    }

    public function show($id)
    {
        try {
            $response = Http::get("{$this->backendUrl}/devices/pending/{$id}");
            
            if ($response->successful()) {
                $data = $response->json();
                return view('pending-devices.show', [
                    'device' => $data['data']
                ]);
            }
            
            return redirect()->route('pending-devices.index')
                           ->with('error', 'Dispositivo não encontrado');
            
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dispositivo: ' . $e->getMessage());
            
            return redirect()->route('pending-devices.index')
                           ->with('error', 'Erro ao carregar dispositivo');
        }
    }

    public function activate($id)
    {
        try {
            // Buscar dados do dispositivo
            $response = Http::get("{$this->backendUrl}/devices/pending/{$id}");
            
            if (!$response->successful()) {
                return redirect()->route('pending-devices.index')
                               ->with('error', 'Dispositivo não encontrado');
            }
            
            $data = $response->json();
            
            // Buscar tipos de dispositivos da API
            $deviceTypesResponse = Http::get("{$this->backendUrl}/mqtt/device-types");
            $deviceTypes = [];
            if ($deviceTypesResponse->successful()) {
                $typesData = $deviceTypesResponse->json();
                if ($typesData['success'] && isset($typesData['data'])) {
                    $deviceTypes = $typesData['data'];
                }
            }
            
            // Fallback para tipos conhecidos se API falhar
            if (empty($deviceTypes)) {
                $deviceTypes = [
                    ['id' => 17, 'name' => 'Sirene de Alerta', 'icon' => 'volume-up'],
                    ['id' => 1, 'name' => 'ESP32-WROOM', 'icon' => 'microchip'],
                    ['id' => 2, 'name' => 'ESP32-S3', 'icon' => 'microchip'],
                    ['id' => 3, 'name' => 'Sensor de Temperatura', 'icon' => 'thermometer'],
                    ['id' => 4, 'name' => 'Relé de Controle', 'icon' => 'toggle-on'],
                    ['id' => 5, 'name' => 'LED Inteligente', 'icon' => 'lightbulb']
                ];
            }
            
            // Buscar departamentos da API
            $departmentsResponse = Http::get("{$this->backendUrl}/mqtt/departments");
            $departments = [];
            if ($departmentsResponse->successful()) {
                $deptsData = $departmentsResponse->json();
                if ($deptsData['success'] && isset($deptsData['data'])) {
                    $departments = $deptsData['data'];
                }
            }
            
            // Fallback para departamentos conhecidos se API falhar
            if (empty($departments)) {
                $departments = [
                    ['id' => 2, 'name' => 'Produção'],
                    ['id' => 1, 'name' => 'TI - Tecnologia da Informação'], 
                    ['id' => 3, 'name' => 'Qualidade'],
                    ['id' => 4, 'name' => 'Manutenção'],
                    ['id' => 5, 'name' => 'Administração']
                ];
            }
            
            return view('pending-devices.activate', [
                'device' => $data['data'],
                'deviceTypes' => $deviceTypes,
                'departments' => $departments
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao carregar página de ativação: ' . $e->getMessage());
            
            return redirect()->route('pending-devices.index')
                           ->with('error', 'Erro ao carregar página de ativação');
        }
    }

    public function processActivation(Request $request, $id)
    {
        try {
            $request->validate([
                'device_type' => 'required|integer',
                'department' => 'required|integer'
            ]);

            // Simular token de autenticação (em produção usar JWT real)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer fake-token',
                'Content-Type' => 'application/json'
            ])->post("{$this->backendUrl}/devices/pending/{$id}/activate", [
                'device_type' => $request->device_type,
                'department' => $request->department
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return redirect()->route('pending-devices.index')
                               ->with('success', $data['message'] ?? 'Dispositivo ativado com sucesso!');
            } else {
                $error = $response->json()['message'] ?? 'Erro ao ativar dispositivo';
                return back()->with('error', $error)->withInput();
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Erro ao ativar dispositivo: ' . $e->getMessage());
            
            return back()->with('error', 'Erro interno do servidor')->withInput();
        }
    }

    // AJAX Methods
    public function stats()
    {
        try {
            $response = Http::get("{$this->backendUrl}/devices/pending");
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'stats' => $data['stats'] ?? []
                ]);
            }
            
            return response()->json(['success' => false]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function findByMac(Request $request)
    {
        try {
            $request->validate([
                'mac_address' => 'required|string'
            ]);

            $response = Http::get("{$this->backendUrl}/devices/pending/find-by-mac", [
                'mac_address' => $request->mac_address
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json(['success' => false]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function refresh()
    {
        try {
            $response = Http::get("{$this->backendUrl}/devices/pending");
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'data' => $data['data'] ?? [],
                    'stats' => $data['stats'] ?? []
                ]);
            }
            
            return response()->json(['success' => false]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer fake-token'
            ])->post("{$this->backendUrl}/devices/pending/{$id}/reject");

            if ($response->successful()) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Dispositivo rejeitado com sucesso']);
                }
                return redirect()->route('pending-devices.index')
                               ->with('success', 'Dispositivo rejeitado com sucesso');
            }
            
            return response()->json(['success' => false, 'message' => 'Erro ao rejeitar dispositivo']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno']);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer fake-token'
            ])->delete("{$this->backendUrl}/devices/pending/{$id}");

            if ($response->successful()) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Dispositivo excluído com sucesso']);
                }
                return redirect()->route('pending-devices.index')
                               ->with('success', 'Dispositivo excluído com sucesso');
            }
            
            return response()->json(['success' => false, 'message' => 'Erro ao excluir dispositivo']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno']);
        }
    }
} 