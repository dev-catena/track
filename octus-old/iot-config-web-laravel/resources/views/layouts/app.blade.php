<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Octus') }} - @yield('title', 'Sistema IoT')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/octus-icon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f7fafc;
            color: #2d3748;
        }
        /* Navbar */
        .navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .navbar-brand {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .navbar-logo {
            width: 40px;
            height: 40px;
        }
        .navbar-title {
            font-size: 20px;
            color: #3E4A59;
        }
        .navbar-menu {
            display: flex;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        .navbar-link {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .navbar-link:hover {
            background: #edf2f7;
            color: #3E4A59;
        }
        .navbar-link.active {
            background: #3E4A59;
            color: white;
        }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .navbar-user-name {
            font-size: 14px;
            color: #4a5568;
        }
        .navbar-logout {
            display: inline;
        }
        .navbar-logout-btn {
            padding: 6px 16px;
            background: #fc8181;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .navbar-logout-btn:hover {
            background: #f56565;
        }
        /* Main Content */
        main {
            max-width: 1400px;
            margin: 32px auto;
            padding: 0 20px;
        }
        /* Utility Classes */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3E4A59;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
        }
        .btn-secondary {
            background: #cbd5e0;
            color: #2d3748;
        }
        .btn-secondary:hover {
            background: #a0aec0;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        h1 { font-size: 32px; font-weight: 700; margin-bottom: 16px; color: #1a202c; }
        h2 { font-size: 24px; font-weight: 600; margin-bottom: 12px; color: #2d3748; }
        h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #4a5568; }
    </style>
    
    <!-- Dashboard CSS -->
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet" type="text/css">
    
    <!-- Additional styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div id="app">
        @if(auth()->check())
            <!-- Navigation -->
            <nav class="navbar">
                <div class="navbar-container">
                    <div class="navbar-content">
                        <div class="navbar-brand">
                            <img src="{{ asset('images/octus-icon.svg') }}" alt="Octus Logo" class="navbar-logo">
                            <h1 class="navbar-title">Octus</h1>
                        </div>
                        <div class="navbar-menu">
                            <a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('companies.index') }}" class="navbar-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                                Empresas
                            </a>
                            <a href="{{ route('departments.index') }}" class="navbar-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                                Departamentos
                            </a>
                            <a href="{{ route('device-types.index') }}" class="navbar-link {{ request()->routeIs('device-types.*') ? 'active' : '' }}">
                                Tipos de Dispositivo
                            </a>
                            <a href="{{ route('users.index') }}" class="navbar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                Usuários
                            </a>
                            <a href="{{ route('topics.index') }}" class="navbar-link {{ request()->routeIs('topics.*') ? 'active' : '' }}">
                                Tópicos MQTT
                            </a>
                            <a href="{{ route('ota-updates.index') }}" class="navbar-link {{ request()->routeIs('ota-updates.*') ? 'active' : '' }}">
                                Logs OTA
                            </a>
                        </div>
                        <div class="navbar-user">
                            <span class="navbar-user-name">Olá, {{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="navbar-logout">
                                @csrf
                                <button type="submit" class="navbar-logout-btn">
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endif

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>

