<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $theme = $request->cookie('theme');

        if (!$theme && $request->user()) {
            $user = $request->user()->load('systemConfiguration');
            if ($user->systemConfiguration && $user->systemConfiguration->theme) {
                $theme = $user->systemConfiguration->theme == 'light'
                    ? 'white-content'
                    : 'dark-content';
            }
        }

        $theme = $theme ?? 'dark-content';
        view()->share('theme', $theme);

        $response = $next($request);
        if (!$request->cookie('theme')) {
            $response->headers->setCookie(
                cookie('theme', $theme, 43200, '/')
            );
        }

        return $response;
    }
}
