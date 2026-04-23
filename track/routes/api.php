<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\OperatorApiController;
use App\Http\Controllers\Api\PendingDeviceController;
use App\Http\Controllers\Api\SelfServiceController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\DockSlotStatusController;

// Registro ESP32 (público - sem auth)
Route::post('/devices/pending', [PendingDeviceController::class, 'store']);

// Check-in doca já ativada (só atualiza rede - não aparece em Docas Pendentes)
Route::post('/devices/checkin', [PendingDeviceController::class, 'checkin']);

// Autoatendimento - tablet (público)
Route::get('/self-service/docks', [SelfServiceController::class, 'index']);
Route::post('/self-service/open', [SelfServiceController::class, 'open']);
Route::post('/self-service/close', [SelfServiceController::class, 'close']);

// ESP32 envia status dos slots (público)
Route::post('/docks/slot-status', [DockSlotStatusController::class, 'store']);

//Auth apis
Route::prefix('auth')
->group(function () {
    // Login endpoints for operators
    //Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/v2/login', [AuthApiController::class, 'loginV2']);
    // Login para admin (tablet - configurar doca, gravar rostos)
    Route::post('/admin/login', [AuthApiController::class, 'adminLogin']);
    // Mock: login sem credenciais para teste de reconhecimento facial (retorna 1º operador ativo)
    Route::post('/tablet-mock', [AuthApiController::class, 'tabletMock']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Operator dashboard
    Route::get('/dashboard', [OperatorApiController::class, 'dashboard']);

    // Operator reports
    Route::get('/reports', [OperatorApiController::class, 'reports']);

    // Validate user via face auth before device access
    Route::post('/user/validate', [OperatorApiController::class, 'validateUser']);

    // Mock checkout (sem validação facial - para desenvolvimento)
    Route::post('/device/checkout-mock', [OperatorApiController::class, 'deviceCheckoutMock']);

    // Check-in a device
    Route::post('/device/checkin', [OperatorApiController::class, 'deviceCheckin']);

    // Capture device location
    Route::post('/device/location/capture', [OperatorApiController::class, 'deviceLocationCapture']);

    // Dispositivos pendentes (admin) - ativar cria tópico MQTT
    Route::get('/devices/pending', [PendingDeviceController::class, 'index']);
    Route::post('/devices/pending/{id}/activate', [PendingDeviceController::class, 'activate']);

    // Admin tablet: listar operadores/usuários e registrar rostos
    Route::get('/admin/operators', [AdminApiController::class, 'operators']);
    Route::post('/admin/operators/{id}/face-register', [AdminApiController::class, 'faceRegister']);
    Route::post('/admin/users/{id}/face-register', [AdminApiController::class, 'userFaceRegister']);
});
