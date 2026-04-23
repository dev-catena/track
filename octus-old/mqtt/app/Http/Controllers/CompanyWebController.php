<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyWebController extends Controller
{
    public function show(int $id)
    {
        $company = Company::with(['departments' => fn($q) => $q->orderBy('nivel_hierarquico')->orderBy('name')])
            ->findOrFail($id);
        return view('web.companies.show', ['company' => $company]);
    }

    public function edit(int $id)
    {
        $company = Company::with('departments')->findOrFail($id);
        return view('web.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name,' . $id
        ]);
        $company->update($validated);
        return redirect()->route('companies.show', $id)->with('success', 'Empresa atualizada com sucesso.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name'
        ]);
        $company = Company::create($validated);
        return redirect()->route('companies.show', $company->id)->with('success', 'Empresa criada com sucesso.');
    }

    public function destroy(int $id)
    {
        $company = Company::findOrFail($id);
        if ($company->departments()->count() > 0) {
            return redirect()->route('companies.index')
                ->with('error', 'Não é possível deletar empresa com departamentos.');
        }
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Empresa deletada com sucesso.');
    }

    public function organizationalStructure(int $id)
    {
        $company = Company::with(['departments' => fn($q) => $q->orderBy('nivel_hierarquico')->orderBy('name')])
            ->findOrFail($id);
        $structure = $company->getOrganizationalStructure();
        $stats = \App\Models\Department::getOrganizationalStats($id);
        return view('web.companies.organizational-structure', compact('company', 'structure', 'stats'));
    }
}
