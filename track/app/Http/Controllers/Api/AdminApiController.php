<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\User;
use App\Repositories\Interfaces\OperatorInterface;
use App\Services\ThalamusFaceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminApiController extends Controller
{
    public function __construct(OperatorInterface $operator)
    {
        $this->operator = $operator;
    }

    /**
     * Lista operadores e usuários (admin/manager) da organização para gravar rostos.
     * GET /api/admin/operators
     */
    public function operators(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$this->isAdmin($user)) {
            return $this->sendJsonResponse(0, 'Acesso restrito a administradores.');
        }

        $orgId = $user->organization_id ?? null;
        // Superadmin (sem unidade) usa organization_id da query, como no painel (filtro de empresa).
        if (! $orgId && $user->role === 'superadmin' && $request->filled('organization_id')) {
            $request->validate(['organization_id' => 'required|integer|exists:organizations,id']);
            $orgId = (int) $request->organization_id;
        }

        if (! $orgId) {
            return $this->sendJsonResponse(
                0,
                'Nenhuma unidade selecionada. No app, faça login com superadmin e escolha a unidade; ou use um admin vinculado a uma organização. No painel, contas superadmin não aparecem em Usuários (lista só a unidade).',
                null
            );
        }

        $operators = Operator::select('id', 'name', 'username', 'email', 'face_id', 'organization_id', 'department_id')
            ->where('status', 'active')
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get()
            ->map(fn ($o) => array_merge($o->toArray(), ['type' => 'operator']));

        $users = User::select('id', 'name', 'email', 'face_id', 'organization_id', 'department_id')
            ->where('status', 'active')
            ->where('organization_id', $orgId)
            ->whereIn('role', ['admin', 'manager'])
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => array_merge($u->toArray(), [
                'username' => $u->username ?? null,
                'type' => 'user',
            ]));

        $combined = $operators->concat($users)->sortBy('name')->values()->all();

        return $this->sendJsonResponse(1, 'Operadores listados.', ['operators' => $combined]);
    }

    /**
     * Registra rosto de um operador (admin apenas).
     * POST /api/admin/operators/{id}/face-register
     * Body: image (file)
     */
    public function faceRegister(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$this->isAdmin($user)) {
            return $this->sendJsonResponse(0, 'Acesso restrito a administradores.');
        }

        try {
            $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png|max:4096']);

            $operator = $this->operator->find($id);

            if ($user->role === 'superadmin' && ! $user->organization_id) {
                $request->validate(['organization_id' => 'required|integer|exists:organizations,id']);
                if ((int) $operator->organization_id !== (int) $request->input('organization_id')) {
                    return $this->sendJsonResponse(0, 'Operador não pertence à unidade indicada (organization_id).');
                }
            } elseif ($user->organization_id && (int) $operator->organization_id !== (int) $user->organization_id) {
                return $this->sendJsonResponse(0, 'Operador não pertence à sua organização.');
            }

            $imagePath = $request->file('image')->path();
            $thalamus = new ThalamusFaceService;

            if (! $thalamus->isConfigured()) {
                return $this->sendJsonResponse(0, 'API facial Thalamus não configurada (THALAMUS_FACE_BASE_URL).');
            }

            $faceId = ThalamusFaceService::operatorFaceId((int) $operator->id);
            $result = $thalamus->registerFromImage($imagePath, $faceId);
            if (! $result['ok']) {
                return $this->sendJsonResponse(0, $result['message']);
            }

            DB::transaction(function () use ($id, $faceId, $user) {
                $this->operator->update($id, [
                    'face_id' => $faceId,
                    'updated_by' => $user->id,
                ]);
            });

            return $this->sendJsonResponse(1, $result['message'] ?? 'Rosto registrado com sucesso.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);
        } catch (\Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }

    /**
     * Registra rosto de um usuário (admin/manager) - tabela users.
     * POST /api/admin/users/{id}/face-register
     */
    public function userFaceRegister(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$this->isAdmin($user)) {
            return $this->sendJsonResponse(0, 'Acesso restrito a administradores.');
        }

        try {
            $request->validate(['image' => 'required|file|mimes:jpg,jpeg,png|max:4096']);

            $targetUser = User::findOrFail($id);

            $isSuperUnbound = $user->role === 'superadmin' && ! $user->organization_id;
            if (! $isSuperUnbound && (int) $targetUser->organization_id !== (int) $user->organization_id) {
                return $this->sendJsonResponse(0, 'Usuário não pertence à sua organização.');
            }
            if (!in_array($targetUser->role, ['admin', 'manager'])) {
                return $this->sendJsonResponse(0, 'Apenas admin/manager podem ter rosto cadastrado aqui.');
            }

            $imagePath = $request->file('image')->path();
            $thalamus = new ThalamusFaceService;

            if (! $thalamus->isConfigured()) {
                return $this->sendJsonResponse(0, 'API facial Thalamus não configurada (THALAMUS_FACE_BASE_URL).');
            }

            $faceId = ThalamusFaceService::userFaceId((int) $targetUser->id);
            $result = $thalamus->registerFromImage($imagePath, $faceId);
            if (! $result['ok']) {
                return $this->sendJsonResponse(0, $result['message']);
            }

            $targetUser->update(['face_id' => $faceId]);

            return $this->sendJsonResponse(1, $result['message'] ?? 'Rosto registrado com sucesso.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);
        } catch (\Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }

    private function isAdmin($user): bool
    {
        if (!$user) return false;
        if ($user instanceof \App\Models\User) {
            return in_array($user->role, ['superadmin', 'admin', 'manager']);
        }
        return false;
    }
}
