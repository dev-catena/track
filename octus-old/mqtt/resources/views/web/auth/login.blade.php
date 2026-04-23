<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'Octus Web') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles: Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: Inter, system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-container { width: 100%; max-width: 400px; padding: 1rem; }
        .login-card { background: white; border-radius: 1rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .login-header { text-align: center; margin-bottom: 1.5rem; }
        .login-header h1 { font-size: 1.75rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
        .login-header p { color: #6b7280; font-size: 0.875rem; }
        .login-form .form-group { margin-bottom: 1.25rem; }
        .login-form label { display: block; font-weight: 500; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .login-form .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; }
        .login-form .form-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.2); }
        .login-button { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; border: none; border-radius: 0.5rem; font-size: 1rem; cursor: pointer; }
        .login-button:hover { opacity: 0.95; }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔌 Octus</h1>
                <p>Faça login para acessar o sistema</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="login-form">
                @csrf
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="Digite seu email"
                        class="form-input @error('email') border-red-500 @enderror"
                    />
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            placeholder="Digite sua senha"
                            class="form-input pr-12 @error('password') border-red-500 @enderror"
                        />
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                        >
                            <span id="passwordToggle">👁️</span>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="login-button">
                        🔐 Entrar
                    </button>
                </div>
            </form>

            <!-- Informações adicionais -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">v1.0.0</p>
                <p class="text-sm text-gray-500">Roboflex IoT Solutions</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                passwordToggle.textContent = '👁️';
            }
        }
    </script>
</body>
</html>

