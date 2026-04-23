<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DockController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PendingDeviceWebController;
use App\Http\Controllers\CompanyMapController;
use App\Http\Controllers\OtaReportController;
use App\Http\Controllers\FirmwareController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ReportController;
use App\Services\FCMService;

use Illuminate\Http\Request;
Route::get('/', function () {
    // Redirect root to login page
    return redirect()->route('login');
    //return view('welcome');
});

Auth::routes();

// Theme setting route
Route::post('/set-theme', function (Request $request) {
    $theme = $request->input('theme');
    return response()->json(['status' => 'ok', 'theme' => $theme])
        ->cookie('theme', $theme, 43200, '/');
});

Route::get('/register', function () {
    return redirect()->route('login');
});

// Download de firmware (público - ESP32 precisa acessar)
Route::get('/firmware/download/{filename}', [FirmwareController::class, 'download'])->name('firmware.download');

Route::get('/set-password/{token}', function ($token) {
    $email = request('email');
    return view('auth.passwords.set', compact('token', 'email'));
})->name('set-password');

Route::middleware(['auth', 'role:superadmin'])
->prefix('SuperAdmin')
->group(function () {
    // Sessão: empresa selecionada (multitenant - seletor global)
    Route::post('/session/select-organization', [SessionController::class, 'selectOrganization'])->name('session.select-organization');

    // SuperAdmin Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('superadmin.dashboard');

    // SuperAdmin Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('superadmin.profile');

    // Manage Organization (Company)
    Route::get('/organization', [OrganizationController::class, 'index'])->name('superadmin.organization.index');
    Route::post('/organization', [OrganizationController::class, 'store'])->name('superadmin.organization.store');
    Route::get('/organization/{id}', [OrganizationController::class, 'detail'])->name('superadmin.organization.detail');
    Route::put('/organization/{id}', [OrganizationController::class, 'update'])->name('superadmin.organization.update');
    Route::delete('/organization/{id}', [OrganizationController::class, 'destroy'])->name('superadmin.organization.destroy');

    // Manage Departments
    Route::get('/department', [DepartmentController::class, 'index'])->name('superadmin.department.index');

    // Manage Users
    Route::get('/user', [UserController::class, 'index'])->name('superadmin.user.index');

    // Manage Profiles (CRUD)
    Route::get('/profiles', [ProfileController::class, 'index'])->name('superadmin.profiles.index');

    // Permissões (perfis x funcionalidades)
    Route::get('/permissions', [PermissionController::class, 'index'])->name('superadmin.permissions.index');

    // Manage Docks
    Route::get('/dock/management', [DockController::class, 'index'])->name('superadmin.manage.dock.index');
    Route::get('/dock/panel', [DockController::class, 'panel'])->name('superadmin.manage.dock.panel');

    // Manage Devices
    Route::get('/device', [DeviceController::class, 'index'])->name('superadmin.device.index');

    // Dispositivos pendentes (ESP32) - listar e ativar
    Route::get('/devices/pending', [PendingDeviceWebController::class, 'index'])->name('superadmin.devices.pending');

    // Mapa da empresa (grafo Org → Dept → Docas)
    Route::get('/company-map', [CompanyMapController::class, 'index'])->name('superadmin.company.map');
    Route::get('/ota-report', [OtaReportController::class, 'index'])->name('superadmin.ota.report');
    Route::get('/company-map/data', [CompanyMapController::class, 'data'])->name('superadmin.company.map.data');
    Route::get('/company-map/ping/{dockId}', [CompanyMapController::class, 'ping'])->name('superadmin.company.map.ping');
    Route::get('/company-map/firmware/list', [CompanyMapController::class, 'firmwareList'])->name('superadmin.company.map.firmware.list');
    Route::post('/company-map/firmware/upload', [CompanyMapController::class, 'firmwareUpload'])->name('superadmin.company.map.firmware.upload');
    Route::get('/company-map/ota/count', [CompanyMapController::class, 'otaCount'])->name('superadmin.company.map.ota.count');
    Route::post('/company-map/ota/trigger', [CompanyMapController::class, 'otaTrigger'])->name('superadmin.company.map.ota.trigger');

    // System Configuration
    Route::get('/configuration/system', [ConfigurationController::class, 'index'])->name('superadmin.configuration.index');

    // Bot Configuration
    Route::get('/configuration/bot', [ConfigurationController::class, 'bot'])->name('superadmin.configuration.bot.index');
    Route::post('/configuration/bot', [ConfigurationController::class, 'bot_store'])->name('superadmin.configuration.bot.store');
    Route::get('/configuration/bot/{id}', [ConfigurationController::class, 'bot_detail'])->name('superadmin.configuration.bot.detail');
    Route::put('/configuration/bot/{id}', [ConfigurationController::class, 'bot_update'])->name('superadmin.configuration.bot.update');
    Route::delete('/configuration/bot/{id}', [ConfigurationController::class, 'bot_destroy'])->name('superadmin.configuration.bot.destroy');

    // Activity Logs
    Route::get('/logs', [ActivityController::class, 'index'])->name('superadmin.logs.index');

    // Relatórios (doca / operações por usuário)
    Route::get('/reports/dock-history', [ReportController::class, 'dockHistory'])->name('superadmin.reports.dock-history');
    Route::get('/reports/dock-history/data', [ReportController::class, 'dockHistoryData'])->name('superadmin.reports.dock-history.data');
    Route::get('/reports/dock-history/export', [ReportController::class, 'dockHistoryExport'])->name('superadmin.reports.dock-history.export');
    Route::get('/reports/user-operations', [ReportController::class, 'userOperations'])->name('superadmin.reports.user-operations');
    Route::get('/reports/user-operations/data', [ReportController::class, 'userOperationsData'])->name('superadmin.reports.user-operations.data');
    Route::get('/reports/user-operations/export', [ReportController::class, 'userOperationsExport'])->name('superadmin.reports.user-operations.export');
    Route::post('/reports/ajax-lists', [ReportController::class, 'ajaxLists'])->name('superadmin.reports.ajax-lists');
    
    // Notifications
    Route::get('/notification', [NotificationController::class, 'index'])->name('superadmin.notification.index');
});

Route::middleware(['auth', 'role:admin'])
->prefix('Admin')
->group(function () {

    // Admin Dashboard
    Route::get('/dashboard', [OrganizationController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/graph-stats', [OrganizationController::class, 'dashboardGraphStats'])
    ->name('admin.dashboard.graphStats');

    // Manage Departments
    Route::get('/department', [DepartmentController::class, 'index'])->name('admin.department.index');

    // Manage Docks
    Route::get('/dock/management', [DockController::class, 'index'])->name('admin.manage.dock.index');
    Route::get('/dock/panel', [DockController::class, 'panel'])->name('admin.manage.dock.panel');

    // Manage Devices
    Route::get('/device', [DeviceController::class, 'index'])->name('admin.device.index');

    // Dispositivos pendentes (ESP32) - listar e ativar
    Route::get('/devices/pending', [PendingDeviceWebController::class, 'index'])->name('admin.devices.pending');

    // Mapa da empresa (grafo Org → Dept → Docas)
    Route::get('/company-map', [CompanyMapController::class, 'index'])->name('admin.company.map');
    Route::get('/ota-report', [OtaReportController::class, 'index'])->name('admin.ota.report');
    Route::get('/company-map/data', [CompanyMapController::class, 'data'])->name('admin.company.map.data');
    Route::get('/company-map/ping/{dockId}', [CompanyMapController::class, 'ping'])->name('admin.company.map.ping');
    Route::get('/company-map/firmware/list', [CompanyMapController::class, 'firmwareList'])->name('admin.company.map.firmware.list');
    Route::post('/company-map/firmware/upload', [CompanyMapController::class, 'firmwareUpload'])->name('admin.company.map.firmware.upload');
    Route::get('/company-map/ota/count', [CompanyMapController::class, 'otaCount'])->name('admin.company.map.ota.count');
    Route::post('/company-map/ota/trigger', [CompanyMapController::class, 'otaTrigger'])->name('admin.company.map.ota.trigger');

    // Manage Users
    Route::get('/user', [UserController::class, 'index'])->name('admin.user.index');

    // Permissões
    Route::get('/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');

    // Notifications
    Route::get('/notification', [NotificationController::class, 'index'])->name('admin.notification.index');

    // Configuration
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('admin.configuration.index');

    // Activity Logs
    Route::get('/logs', [ActivityController::class, 'index'])->name('admin.logs.index');

    Route::get('/reports/dock-history', [ReportController::class, 'dockHistory'])->name('admin.reports.dock-history');
    Route::get('/reports/dock-history/data', [ReportController::class, 'dockHistoryData'])->name('admin.reports.dock-history.data');
    Route::get('/reports/dock-history/export', [ReportController::class, 'dockHistoryExport'])->name('admin.reports.dock-history.export');
    Route::get('/reports/user-operations', [ReportController::class, 'userOperations'])->name('admin.reports.user-operations');
    Route::get('/reports/user-operations/data', [ReportController::class, 'userOperationsData'])->name('admin.reports.user-operations.data');
    Route::get('/reports/user-operations/export', [ReportController::class, 'userOperationsExport'])->name('admin.reports.user-operations.export');

    // Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('superadmin.profile');
});

Route::middleware(['auth', 'role:manager', 'permission'])
->prefix('Manager')
->group(function () {
    // Manager Dashboard
    Route::get('/dashboard', [DepartmentController::class, 'dashboard'])->name('manager.dashboard');

    // Manage Docks
    Route::get('/dock/management', [DockController::class, 'index'])->name('manager.manage.dock.index');
    Route::get('/dock/panel', [DockController::class, 'panel'])->name('manager.manage.dock.panel');

    // Manage Devices
    Route::get('/device', [DeviceController::class, 'index'])->name('manager.device.index');

    // Mapa da empresa (grafo Org → Dept → Docas)
    Route::get('/company-map', [CompanyMapController::class, 'index'])->name('manager.company.map');
    Route::get('/ota-report', [OtaReportController::class, 'index'])->name('manager.ota.report');
    Route::get('/company-map/data', [CompanyMapController::class, 'data'])->name('manager.company.map.data');
    Route::get('/company-map/ping/{dockId}', [CompanyMapController::class, 'ping'])->name('manager.company.map.ping');
    Route::get('/company-map/firmware/list', [CompanyMapController::class, 'firmwareList'])->name('manager.company.map.firmware.list');
    Route::post('/company-map/firmware/upload', [CompanyMapController::class, 'firmwareUpload'])->name('manager.company.map.firmware.upload');
    Route::get('/company-map/ota/count', [CompanyMapController::class, 'otaCount'])->name('manager.company.map.ota.count');
    Route::post('/company-map/ota/trigger', [CompanyMapController::class, 'otaTrigger'])->name('manager.company.map.ota.trigger');

    // Manage Users
    Route::get('/user', [UserController::class, 'index'])->name('manager.user.index');

    // Notifications
    Route::get('/notification', [NotificationController::class, 'index'])->name('manager.notification.index');

    // Configuration
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('manager.configuration.index');

    // Activity Logs
    Route::get('/logs', [ActivityController::class, 'index'])->name('manager.logs.index');

    Route::get('/reports/dock-history', [ReportController::class, 'dockHistory'])->name('manager.reports.dock-history');
    Route::get('/reports/dock-history/data', [ReportController::class, 'dockHistoryData'])->name('manager.reports.dock-history.data');
    Route::get('/reports/dock-history/export', [ReportController::class, 'dockHistoryExport'])->name('manager.reports.dock-history.export');
    Route::get('/reports/user-operations', [ReportController::class, 'userOperations'])->name('manager.reports.user-operations');
    Route::get('/reports/user-operations/data', [ReportController::class, 'userOperationsData'])->name('manager.reports.user-operations.data');
    Route::get('/reports/user-operations/export', [ReportController::class, 'userOperationsExport'])->name('manager.reports.user-operations.export');

    // Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('superadmin.profile');
});

// Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


Route::middleware(['auth', 'role:superadmin|admin'])
->group(function () {

    // Permissões - atualizar (apenas superadmin e admin)
    Route::post('/permissions', [PermissionController::class, 'update'])->name('permission.update');

    // Manage Department Actions
    Route::post('/department', [DepartmentController::class, 'store'])->name('department.store');
    Route::get('/department/{id}', [DepartmentController::class, 'detail'])->name('department.detail');
    Route::put('/department/{id}', [DepartmentController::class, 'update'])->name('department.update');
    Route::delete('/department/{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');

});

Route::middleware(['auth', 'role:superadmin|admin|manager'])->group(function () {
    //Manage Dock
    Route::post('/dock/management', [DockController::class, 'store'])->name('dock.store');
    Route::get('/dock/management/{id}', [DockController::class, 'detail'])->name('dock.detail');
    Route::post('/dock/management/{id}/regenerate-pairing', [DockController::class, 'regeneratePairing'])->name('dock.regenerate.pairing');
    Route::put('/dock/management/{id}', [DockController::class, 'update'])->name('dock.update');
    Route::delete('/dock/management/{id}', [DockController::class, 'destroy'])->name('dock.destroy');
    Route::get('/mqtt/topics/{id}', [DockController::class, 'get_mqtt_topics'])->name('dock.get_mqtt_topics');

    //Manage Device
    Route::post('/device', [DeviceController::class, 'store'])->name('device.store');

    // Ativar dispositivo pendente (cria tópico MQTT)
    Route::post('/devices/pending/{id}/activate', [PendingDeviceWebController::class, 'activate'])->name('devices.pending.activate');
    Route::post('/devices/pending/revert', [PendingDeviceWebController::class, 'revertToPending'])->name('devices.pending.revert');
    Route::get('/device/{id}', [DeviceController::class, 'detail'])->name('device.detail');
    Route::put('/device/{id}', [DeviceController::class, 'update'])->name('device.update');
    Route::delete('/device/{id}', [DeviceController::class, 'destroy'])->name('device.destroy');

    // Perfis: somente leitura (definidos por seed/migração; permissões em /permissions)

    //Manage User
    Route::post('/user', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{id}', [UserController::class, 'detail'])->name('user.detail');
    Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    Route::post('/user/{id}/change-password', [UserController::class, 'changePassword'])->name('user.change.password');
    Route::get('/department_list_by_company/{id}', [DepartmentController::class, 'department_list_by_company'])->name('department_list_by_company');

    //Manage Operators
    Route::get('/operator/{id}', [OperatorController::class, 'detail'])->name('operator.detail');
    Route::put('/operator/{id}', [OperatorController::class, 'update'])->name('operator.update');
    Route::delete('/operator/{id}', [OperatorController::class, 'destroy'])->name('operator.destroy');
    //Route::post('/operator/{id}/face-register', [OperatorController::class, 'faceRegister'])->name('operator.face.register');
    //Route::get('/operator/{id}/face-detail', [OperatorController::class, 'faceDetail'])->name('operator.face.detail');
    Route::post('/operator/{id}/change-password', [OperatorController::class, 'changePassword'])->name('operator.change.password');


    Route::post('/operator/{id}/face-register', [OperatorController::class, 'faceRegisterV2'])->name('operator.face.register');
    Route::get('/operator/{id}/face-detail', [OperatorController::class, 'faceDetailV2'])->name('operator.face.detail');

    //system configuration
    Route::put('/configuration/system/{id}', [ConfigurationController::class, 'update'])->name('configuration.system.update');

});


Route::get('/test-fcm', function(FCMService $fcm) {

    $token = "eT700wjdSBiL2yZUg-0QJF:APA91bFv86T8Xma2FUJUSpS2OIagjC1HRMo7LgznwK_FIW3LiD-QABhBB8LsbyEWLEM0djfcv8KDVXThXzTB19VeyDcVW1gbQtPE-U0-6IjimBeyuyEf9O8";

    return $fcm->sendToToken(
        $token,
        "Test Push",
        "Firebase setup successful!",
        ['event' => 'test']
    );
});
