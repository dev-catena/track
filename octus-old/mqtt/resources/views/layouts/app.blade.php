<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Octus Web') }} - @yield('title', 'Sistema IoT')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles: Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } }
    </script>
    <style>
        .navbar { background: white; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .navbar-container { max-width: 80rem; margin: 0 auto; padding: 0 1rem; }
        .navbar-content { display: flex; align-items: center; justify-content: space-between; height: 4rem; }
        .navbar-brand { font-weight: 700; font-size: 1.25rem; color: #1f2937; }
        .navbar-menu { display: flex; gap: 1.5rem; }
        .navbar-link { color: #4b5563; font-weight: 500; text-decoration: none; transition: color 0.2s; }
        .navbar-link:hover { color: #111827; }
        .navbar-link.active { color: #2563eb; }
        .navbar-user { display: flex; align-items: center; gap: 1rem; }
        .navbar-user-name { color: #374151; font-size: 0.875rem; }
        .navbar-logout-btn { background: #f3f4f6; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; border: none; cursor: pointer; transition: background 0.2s; }
        .navbar-logout-btn:hover { background: #e5e7eb; }
        main { max-width: 80rem; margin: 0 auto; padding: 2rem 1rem; }
        .admin-dashboard .page-header { margin-bottom: 1.5rem; }
        .admin-dashboard .page-header h1 { font-size: 1.5rem; font-weight: 700; color: #1f2937; }
        .admin-dashboard .page-header p { color: #6b7280; margin-top: 0.25rem; }
        .dashboard-card { background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        .dashboard-card h2 { font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; }
        .dashboard-card p { color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem; }
        .card-btn { padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; text-decoration: none; display: inline-block; }
        .card-btn.primary { background: #2563eb; color: white; }
        .card-btn.primary:hover { background: #1d4ed8; }
        .card-btn.secondary { background: #f3f4f6; color: #374151; }
        .card-btn.secondary:hover { background: #e5e7eb; }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div id="app">
        @if(auth()->check())
            <nav class="navbar">
                <div class="navbar-container">
                    <div class="navbar-content">
                        <div class="navbar-brand"><h1 class="navbar-title">🔌 Octus</h1></div>
                        <div class="navbar-menu">
                            <a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                            <a href="{{ route('companies.index') }}" class="navbar-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">🏢 Empresas</a>
                            <a href="{{ route('departments.index') }}" class="navbar-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">Departamentos</a>
                            <a href="{{ route('device-types.index') }}" class="navbar-link {{ request()->routeIs('device-types.*') ? 'active' : '' }}">Tipos de Dispositivo</a>
                            <a href="{{ route('users.index') }}" class="navbar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Usuários</a>
                            <a href="{{ route('topics.index') }}" class="navbar-link {{ request()->routeIs('topics.*') ? 'active' : '' }}">Tópicos MQTT</a>
                            <a href="{{ route('ota-updates.index') }}" class="navbar-link {{ request()->routeIs('ota-updates.*') ? 'active' : '' }}">📊 Logs OTA</a>
                        </div>
                        <div class="navbar-user">
                            <span class="navbar-user-name">Olá, {{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="navbar-logout">
                                @csrf
                                <button type="submit" class="navbar-logout-btn">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endif
        <main>@yield('content')</main>
    </div>
    @stack('scripts')
</body>
</html>
