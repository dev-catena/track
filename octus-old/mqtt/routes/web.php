<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyWebController;
use App\Http\Controllers\DepartmentWebController;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\DeviceType;
use App\Models\Topic;

// Rota raiz redireciona para login
Route::get('/', function () {
    return redirect('/login');
});

// Rotas de autenticação web
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'webLogin']);
Route::post('/logout', [AuthController::class, 'webLogout'])->name('logout');

// Rotas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $stats = [
            'totalUsers' => User::count(),
            'activeUsers' => User::count(),
            'totalTopics' => Topic::count(),
            'activeDevices' => 0,
        ];
        return view('web.dashboard', compact('stats'));
    })->name('dashboard');

    Route::get('/users', function () {
        return view('web.users.index');
    })->name('users.index');

    Route::get('/topics', function () {
        $stats = [
            'totalTopics' => Topic::count(),
            'activeTopics' => Topic::where('is_active', true)->count(),
            'deviceTopics' => Topic::count(),
            'systemTopics' => 0,
        ];
        return view('web.topics.index', compact('stats'));
    })->name('topics.index');

    Route::get('/companies', function () {
        $query = Company::withCount('departments');
        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }
        $companies = $query->orderBy('name')->get();
        return view('web.companies.index', compact('companies'));
    })->name('companies.index');

    Route::get('/companies/create', function () {
        return view('web.companies.create');
    })->name('companies.create');

    Route::post('/companies', [CompanyWebController::class, 'store'])->name('companies.store');
    Route::get('/companies/{id}', [CompanyWebController::class, 'show'])->name('companies.show');
    Route::get('/companies/{id}/edit', [CompanyWebController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{id}', [CompanyWebController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{id}', [CompanyWebController::class, 'destroy'])->name('companies.destroy');
    Route::get('/companies/{id}/organizational-structure', [CompanyWebController::class, 'organizationalStructure'])->name('companies.organizational-structure');

    Route::get('/departments', function () {
        $query = Department::with('company');
        if (request('company_id')) {
            $query->where('id_comp', request('company_id'));
        }
        if (request('nivel_hierarquico')) {
            $query->where('nivel_hierarquico', request('nivel_hierarquico'));
        }
        $departments = $query->orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        return view('web.departments.index', compact('departments', 'companies'));
    })->name('departments.index');

    Route::get('/departments/create', [DepartmentWebController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentWebController::class, 'store'])->name('departments.store');
    Route::get('/departments/{id}', [DepartmentWebController::class, 'show'])->name('departments.show');
    Route::get('/departments/{id}/edit', [DepartmentWebController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{id}', [DepartmentWebController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{id}', [DepartmentWebController::class, 'destroy'])->name('departments.destroy');

    Route::get('/device-types', function () {
        $deviceTypes = DeviceType::orderBy('name')->get();
        return view('web.device-types.index', compact('deviceTypes'));
    })->name('device-types.index');

    Route::get('/ota-updates', function () {
        return view('web.device-types.ota-updates');
    })->name('ota-updates.index');

    Route::get('/devices', function () {
        return view('web.device-types.index');
    })->name('devices.index');
});
