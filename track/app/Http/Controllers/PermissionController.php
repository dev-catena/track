<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Functionality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $profiles = Profile::orderBy('sort_order')->orderBy('name')->get();
        $functionalities = Functionality::orderBy('platform')->orderBy('sort_order')->get();

        $matrix = [];
        foreach ($profiles as $profile) {
            $ids = $profile->functionalities()->pluck('functionalities.id')->toArray();
            $matrix[$profile->id] = $ids;
        }

        return view('common.permissions.index', compact('profiles', 'functionalities', 'matrix'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'functionality_ids' => 'array',
            'functionality_ids.*' => 'exists:functionalities,id',
        ]);

        $profile = Profile::findOrFail($request->profile_id);
        $profile->functionalities()->sync($request->functionality_ids ?? []);

        return response()->json(['status' => 1, 'message' => 'Permissões atualizadas com sucesso.']);
    }
}
