<?php

namespace App\Providers;

use App\Http\Controllers\SessionController;
use App\Models\Organization;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Compose para layouts.main e layouts.sidebar - garante que $organizations e $selectedOrganizationId
        // estejam disponíveis no seletor de empresa (global-org-selector) para superadmin
        View::composer(['layouts.main', 'layouts.sidebar'], function ($view) {
            $user = Auth::user();
            $organizations = collect();
            $selectedOrganizationId = null;

            if ($user?->role === 'superadmin') {
                $organizations = Organization::where('status', 'active')->orderBy('name')->pluck('name', 'id');
                $selectedOrganizationId = session(SessionController::SESSION_KEY);
                if (!$selectedOrganizationId && $organizations->isNotEmpty()) {
                    $selectedOrganizationId = $organizations->keys()->first();
                    session([SessionController::SESSION_KEY => $selectedOrganizationId]);
                }
            }

            $view->with(compact('organizations', 'selectedOrganizationId'));
        });
    }
}
