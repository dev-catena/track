<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Profile;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (in_array($user->role, ['superadmin', 'admin'])) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        $map = config('route_functionality.map', []);
        $slug = $map[$routeName] ?? null;
        if (!$slug) {
            return $next($request);
        }

        $profile = Profile::where('code', $user->role)->first();
        if (!$profile || !$profile->hasFunctionality($slug)) {
            abort(403, 'Sem permissão para acessar esta página.');
        }

        return $next($request);
    }
}
