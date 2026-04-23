<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\DeviceGroupController;
use App\Http\Controllers\PendingDeviceController;
use App\Http\Controllers\Api\OtaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Autenticação
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');

// Rotas protegidas por autenticação JWT
Route::middleware(['auth:api'])->group(function () {
    // Usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Empresas
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
    });

    // Departamentos
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::get('/{id}', [DepartmentController::class, 'show']);
        Route::put('/{id}', [DepartmentController::class, 'update']);
        Route::delete('/{id}', [DepartmentController::class, 'destroy']);
    });

    // Device Groups
    Route::prefix('device-groups')->group(function () {
        Route::get('/', [DeviceGroupController::class, 'index']);
        Route::post('/', [DeviceGroupController::class, 'store']);
        Route::get('/{id}', [DeviceGroupController::class, 'show']);
        Route::put('/{id}', [DeviceGroupController::class, 'update']);
        Route::delete('/{id}', [DeviceGroupController::class, 'destroy']);
    });

    // Pending Devices
    Route::prefix('pending-devices')->group(function () {
        Route::get('/', [PendingDeviceController::class, 'index']);
        Route::post('/', [PendingDeviceController::class, 'store']);
        Route::get('/{id}', [PendingDeviceController::class, 'show']);
        Route::put('/{id}', [PendingDeviceController::class, 'update']);
        Route::delete('/{id}', [PendingDeviceController::class, 'destroy']);
        Route::post('/{id}/activate', [PendingDeviceController::class, 'activate']);
    });
});

// Rota de teste simples
Route::post('/test-esp32', function(Request $request) {
    return response()->json(['success' => true, 'message' => 'ESP32 test OK', 'data' => $request->all()]);
});

// Rotas para ESP32 (compatibilidade)
Route::prefix('devices')->group(function () {
    // Endpoint para registro de dispositivos ESP32 - ROTA TEMPORÁRIA SIMPLIFICADA
    Route::post('/pending', function(Request $request) {
        try {
            \Log::info('ESP32 Direct Route', ['data' => $request->all()]);
            
            // Verificar se já existe
            $existingDevice = \App\Models\PendingDevice::where('mac_address', $request->mac_address)->first();
            
            if ($existingDevice) {
                // Atualizar dispositivo existente
                $existingDevice->update([
                    'device_name' => $request->device_name,
                    'ip_address' => $request->ip_address,
                    'wifi_ssid' => $request->wifi_ssid,
                    'registered_at' => $request->registered_at ?? time() * 1000,
                    'device_info' => is_string($request->device_info) ? json_encode(['description' => $request->device_info]) : $request->device_info,
                ]);
                
                return response()->json(['success' => true, 'message' => 'Dispositivo atualizado', 'data' => $existingDevice->fresh()]);
            } else {
                // Criar novo dispositivo
                $device = \App\Models\PendingDevice::create([
                    'mac_address' => $request->mac_address,
                    'device_name' => $request->device_name,
                    'ip_address' => $request->ip_address,
                    'wifi_ssid' => $request->wifi_ssid,
                    'registered_at' => $request->registered_at ?? time() * 1000,
                    'device_info' => is_string($request->device_info) ? json_encode(['description' => $request->device_info]) : $request->device_info,
                    'status' => 'pending'
                ]);
                
                return response()->json(['success' => true, 'message' => 'Dispositivo registrado', 'data' => $device]);
            }
        } catch (\Exception $e) {
            \Log::error('Erro na rota simplificada: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });
    //Route::post('/pending', [PendingDeviceController::class, 'store']);
    Route::get('/pending', [PendingDeviceController::class, 'index']);
    Route::get('/pending/{id}', [PendingDeviceController::class, 'show']);
    Route::post('/pending/{id}/activate', [PendingDeviceController::class, 'activate']);
    Route::delete('/pending/{id}', [PendingDeviceController::class, 'destroy']);
    Route::get('/pending/find-by-mac', [PendingDeviceController::class, 'findByMac']);
    Route::get('/pending/stats', [PendingDeviceController::class, 'stats']);
});

// Rotas MQTT (com prefixo específico) - requerem autenticação JWT (consumidas pelo Track)
Route::prefix('mqtt')->middleware(['auth:api'])->group(function () {
    // Enviar comando MQTT via API REST
    Route::post('/send-command', [TopicController::class, 'sendCommand']);
    
    // Topics
    Route::get('/topics', [TopicController::class, 'index']);
    Route::post('/topics', [TopicController::class, 'store']);
    Route::get('/topics/{id}', [TopicController::class, 'show']);
    Route::put('/topics/{id}', [TopicController::class, 'update']);
    Route::patch('/topics/{id}/deactivate', [TopicController::class, 'deactivate']);
    Route::delete('/topics/{id}', [TopicController::class, 'destroy']);

    // Device Types
    Route::get('/device-types', [\App\Http\Controllers\DeviceTypeController::class, 'index']);
    Route::post('/device-types', [\App\Http\Controllers\DeviceTypeController::class, 'store']);
    Route::get('/device-types/{id}', [\App\Http\Controllers\DeviceTypeController::class, 'show']);
    Route::put('/device-types/{id}', [\App\Http\Controllers\DeviceTypeController::class, 'update']);
    Route::delete('/device-types/{id}', [\App\Http\Controllers\DeviceTypeController::class, 'destroy']);
    Route::patch('/device-types/{id}/toggle-status', [\App\Http\Controllers\DeviceTypeController::class, 'toggleStatus']);
    
    // OTA - Firmware Updates
    Route::post('/device-types/{id}/ota-update', [OtaController::class, 'triggerUpdate']);
    Route::get('/device-types/{id}/firmware-info', [OtaController::class, 'getFirmwareInfo']);
    
    // OTA Updates Management
    Route::get('/ota-updates', [OtaController::class, 'listUpdates']);
    Route::get('/ota-updates/{id}', [OtaController::class, 'getUpdateStatus']);
    Route::get('/ota-updates/{id}/logs', [OtaController::class, 'getUpdateLogs']);
    Route::post('/ota-updates/{id}/cancel', [OtaController::class, 'cancelUpdate']);
    Route::post('/ota-updates/{id}/device-feedback', [OtaController::class, 'deviceFeedback']);
    
    // OTA Statistics
    Route::get('/ota-stats', [OtaController::class, 'getStats']);

    /*
     * Companies e Departments são consumidos via /api/companies e /api/departments
     * (rotas autenticadas no grupo auth:api). O Track usa essas rotas após login.
     * Não duplicar aqui para evitar inconsistências.
     */
});
