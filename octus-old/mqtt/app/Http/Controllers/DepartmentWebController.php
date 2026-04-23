<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Company;
use Illuminate\Http\Request;

class DepartmentWebController extends Controller
{
    public function create()
    {
        $companies = Company::orderBy('name')->get();
        $departments = Department::with('company')->orderBy('nivel_hierarquico')->orderBy('name')->get();
        return view('web.departments.create', compact('companies', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nivel_hierarquico' => 'required|integer|min:1',
            'id_unid_up' => 'nullable|integer|exists:departments,id',
            'id_comp' => 'required|integer|exists:companies,id'
        ]);

        if ($validated['nivel_hierarquico'] > 1 && empty($validated['id_unid_up'])) {
            return redirect()->back()->withInput()->with('error', 'Departamentos de nível superior a 1 devem ter uma unidade superior');
        }

        if (!empty($validated['id_unid_up'])) {
            $parent = Department::find($validated['id_unid_up']);
            if ($parent->id_comp != $validated['id_comp']) {
                return redirect()->back()->withInput()->with('error', 'A unidade superior deve pertencer à mesma companhia');
            }
            if ($validated['nivel_hierarquico'] != $parent->nivel_hierarquico + 1) {
                return redirect()->back()->withInput()->with('error', 'O nível hierárquico deve ser o nível da unidade superior + 1');
            }
        } else {
            if ($validated['nivel_hierarquico'] != 1) {
                return redirect()->back()->withInput()->with('error', 'Departamentos sem unidade superior devem ser de nível 1');
            }
        }

        $department = Department::create($validated);
        return redirect()->route('departments.show', $department->id)->with('success', 'Departamento criado com sucesso.');
    }

    public function show(int $id)
    {
        $department = Department::with(['company', 'parent', 'children'])->findOrFail($id);
        return view('web.departments.show', ['department' => $department]);
    }

    public function edit(int $id)
    {
        $department = Department::findOrFail($id);
        $companies = Company::orderBy('name')->get();
        $departments = Department::where('id', '!=', $id)->with('company')->orderBy('nivel_hierarquico')->orderBy('name')->get();
        return view('web.departments.edit', compact('department', 'companies', 'departments'));
    }

    public function update(Request $request, int $id)
    {
        $department = Department::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nivel_hierarquico' => 'required|integer|min:1',
            'id_unid_up' => 'nullable|integer|exists:departments,id',
            'id_comp' => 'required|integer|exists:companies,id'
        ]);

        if ($validated['id_unid_up'] == $id) {
            return redirect()->back()->withInput()->with('error', 'Um departamento não pode ser sua própria unidade superior');
        }

        if ($validated['nivel_hierarquico'] > 1 && empty($validated['id_unid_up'])) {
            return redirect()->back()->withInput()->with('error', 'Departamentos de nível superior a 1 devem ter uma unidade superior');
        }

        if (!empty($validated['id_unid_up'])) {
            $parent = Department::find($validated['id_unid_up']);
            if ($parent->id_comp != $validated['id_comp']) {
                return redirect()->back()->withInput()->with('error', 'A unidade superior deve pertencer à mesma companhia');
            }
            if ($validated['nivel_hierarquico'] != $parent->nivel_hierarquico + 1) {
                return redirect()->back()->withInput()->with('error', 'O nível hierárquico deve ser o nível da unidade superior + 1');
            }
        } else {
            if ($validated['nivel_hierarquico'] != 1) {
                return redirect()->back()->withInput()->with('error', 'Departamentos sem unidade superior devem ser de nível 1');
            }
        }

        $department->update($validated);
        return redirect()->route('departments.show', $id)->with('success', 'Departamento atualizado com sucesso.');
    }

    public function destroy(int $id)
    {
        $department = Department::findOrFail($id);
        if ($department->children()->count() > 0) {
            return redirect()->route('departments.index')
                ->with('error', 'Não é possível deletar departamento com unidades subordinadas.');
        }
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Departamento deletado com sucesso.');
    }
}
