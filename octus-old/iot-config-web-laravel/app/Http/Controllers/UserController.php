<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('app.api_base_url', 'http://localhost:8000/api');
    }

    /**
     * Obter o token de autenticação da sessão
     */
    private function getAuthToken()
    {
        return session('api_token');
    }

    /**
     * Listar todos os usuários
     */
    public function index(Request $request)
    {
        try {
            $token = $this->getAuthToken();
            
            if (!$token) {
                return view('users.index')->with([
                    'error' => '⚠️ Token de API não encontrado. Por favor, faça login novamente.',
                    'users' => [],
                    'pagination' => [],
                    'filters' => []
                ]);
            }
            
            // Construir query parameters
            $queryParams = [];
            if ($request->has('search')) {
                $queryParams['search'] = $request->get('search');
            }
            if ($request->has('tipo')) {
                $queryParams['tipo'] = $request->get('tipo');
            }
            if ($request->has('id_comp')) {
                $queryParams['id_comp'] = $request->get('id_comp');
            }
            if ($request->has('per_page')) {
                $queryParams['per_page'] = $request->get('per_page');
            }

            $response = Http::withToken($token)->get($this->apiBaseUrl . '/users', $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                
                return view('users.index', [
                    'users' => $data['data']['data'] ?? [],
                    'pagination' => $data['data'] ?? [],
                    'filters' => $queryParams
                ]);
            }

            Log::error('Erro ao carregar usuários da API: Status ' . $response->status());
            return view('users.index')->with([
                'error' => 'Erro ao carregar usuários. Por favor, tente novamente.',
                'users' => [],
                'pagination' => [],
                'filters' => $queryParams
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar usuários: ' . $e->getMessage());
            return view('users.index')->with([
                'error' => 'Erro ao conectar com a API: ' . $e->getMessage(),
                'users' => [],
                'pagination' => [],
                'filters' => []
            ]);
        }
    }

    /**
     * Exibir formulário de criação
     */
    public function create()
    {
        try {
            $token = $this->getAuthToken();
            
            // Buscar empresas para o select
            $companiesResponse = Http::withToken($token)->get($this->apiBaseUrl . '/companies');
            $companies = [];
            
            if ($companiesResponse->successful()) {
                $companiesData = $companiesResponse->json();
                $companies = $companiesData['data']['data'] ?? $companiesData['data'] ?? [];
            }

            return view('users.create', compact('companies'));

        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de criação: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Erro ao carregar formulário');
        }
    }

    /**
     * Armazenar novo usuário
     */
    public function store(Request $request)
    {
        try {
            $token = $this->getAuthToken();
            
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone' => $request->phone,
                'id_comp' => $request->id_comp,
                'tipo' => $request->tipo,
            ];

            $response = Http::withToken($token)->post($this->apiBaseUrl . '/users', $data);

            if ($response->successful()) {
                return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso!');
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro ao criar usuário';
            
            if (isset($errorData['errors'])) {
                $errors = collect($errorData['errors'])->flatten()->implode(', ');
                $errorMessage .= ': ' . $errors;
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao conectar com a API: ' . $e->getMessage());
        }
    }

    /**
     * Exibir detalhes de um usuário
     */
    public function show($id)
    {
        try {
            $token = $this->getAuthToken();
            
            $response = Http::withToken($token)->get($this->apiBaseUrl . '/users/' . $id);

            if ($response->successful()) {
                $data = $response->json();
                $user = $data['data'] ?? null;
                
                if ($user) {
                    return view('users.show', compact('user'));
                }
            }

            return redirect()->route('users.index')->with('error', 'Usuário não encontrado');

        } catch (\Exception $e) {
            Log::error('Erro ao exibir usuário: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Erro ao carregar usuário');
        }
    }

    /**
     * Exibir formulário de edição
     */
    public function edit($id)
    {
        try {
            $token = $this->getAuthToken();
            
            // Buscar dados do usuário
            $response = Http::withToken($token)->get($this->apiBaseUrl . '/users/' . $id);
            
            if (!$response->successful()) {
                return redirect()->route('users.index')->with('error', 'Usuário não encontrado');
            }
            
            $data = $response->json();
            $user = $data['data'] ?? null;
            
            // Buscar empresas para o select
            $companiesResponse = Http::withToken($token)->get($this->apiBaseUrl . '/companies');
            $companies = [];
            
            if ($companiesResponse->successful()) {
                $companiesData = $companiesResponse->json();
                $companies = $companiesData['data']['data'] ?? $companiesData['data'] ?? [];
            }

            return view('users.edit', compact('user', 'companies'));

        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de edição: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Erro ao carregar formulário');
        }
    }

    /**
     * Atualizar usuário
     */
    public function update(Request $request, $id)
    {
        try {
            $token = $this->getAuthToken();
            
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'id_comp' => $request->id_comp,
                'tipo' => $request->tipo,
            ];

            // Adicionar senha apenas se informada
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $response = Http::withToken($token)->put($this->apiBaseUrl . '/users/' . $id, $data);

            if ($response->successful()) {
                return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro ao atualizar usuário';
            
            if (isset($errorData['errors'])) {
                $errors = collect($errorData['errors'])->flatten()->implode(', ');
                $errorMessage .= ': ' . $errors;
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao conectar com a API: ' . $e->getMessage());
        }
    }

    /**
     * Excluir usuário
     */
    public function destroy($id)
    {
        try {
            $token = $this->getAuthToken();
            
            $response = Http::withToken($token)->delete($this->apiBaseUrl . '/users/' . $id);

            if ($response->successful()) {
                return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso!');
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro ao excluir usuário';

            return redirect()->route('users.index')->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir usuário: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'Erro ao conectar com a API: ' . $e->getMessage());
        }
    }

    /**
     * Estatísticas de usuários
     */
    public function stats()
    {
        try {
            $token = $this->getAuthToken();
            
            $response = Http::withToken($token)->get($this->apiBaseUrl . '/users/stats');

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conectar com a API'
            ], 500);
        }
    }
}

