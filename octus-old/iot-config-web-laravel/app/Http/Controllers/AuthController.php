<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $apiBaseUrl = config('app.api_base_url', 'http://localhost:8000/api');

        // Tentar autenticar via API MQTT
        try {
            $response = Http::timeout(10)->post($apiBaseUrl . '/login', [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    $userData = $data['data']['user'] ?? $data['user'] ?? null;
                    $token = $data['data']['token'] ?? $data['token'] ?? $data['data']['access_token'] ?? null;
                    
                    if (!$userData || !$token) {
                        throw new \Exception('Resposta da API incompleta');
                    }
                    
                    // Criar usuário local se não existir
                    $user = \App\Models\User::updateOrCreate(
                        ['email' => $userData['email']],
                        [
                            'name' => $userData['name'],
                            'email' => $userData['email'],
                            'tipo' => $userData['tipo'] ?? 'comum',
                            'id_comp' => $userData['id_comp'] ?? null,
                        ]
                    );

                    // Fazer login local
                    Auth::login($user);
                    
                    // Armazenar token da API na sessão
                    $request->session()->put('api_token', $token);
                    $request->session()->save();
                    
                    return redirect()->intended(route('dashboard'));
                }
            }
        } catch (\Exception $e) {
            \Log::warning('API MQTT não disponível: ' . $e->getMessage());
        }

        // Fallback: tentar autenticação local
        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            
            // Avisar o usuário que está sem token da API
            session()->flash('warning', '⚠️ Login realizado localmente. Algumas funcionalidades podem não estar disponíveis. Verifique a conexão com a API.');
            
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}

