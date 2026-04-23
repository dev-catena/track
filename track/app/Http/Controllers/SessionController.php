<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public const SESSION_KEY = 'selected_organization_id';

    /**
     * Salva a organização selecionada na sessão (multitenant).
     * POST /session/select-organization
     */
    public function selectOrganization(Request $request): JsonResponse
    {
        $request->validate(['organization_id' => 'required|integer|exists:organizations,id']);

        $user = Auth::user();
        $orgId = (int) $request->organization_id;

        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Apenas superadmin pode alterar a empresa.'], 403);
        }

        session([self::SESSION_KEY => $orgId]);
        return response()->json(['success' => true, 'message' => 'Empresa selecionada.']);
    }
}
