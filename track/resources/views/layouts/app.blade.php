<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" />
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link
        href="{{ asset('assets/css/black-dashboard.css') }}?v={{ filemtime(public_path('assets/css/black-dashboard.css')) }}"
        rel="stylesheet" />
    <!-- Scripts -->
</head>

<body class="white-content">
    <div id="app">

        <main class="main-panel ps d-flex align-items-center justify-content-center min-vh-100">
            @yield('content')
        </main>
    </div>
</body>

</html>
