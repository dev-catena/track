<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\Interfaces\DepartmentInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Password;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use App\Models\Department;
use App\Repositories\Interfaces\ActivityLogInterface;
use App\Http\Controllers\SessionController;

class DepartmentController extends Controller
{

    protected $organization;
    protected $department;
    protected $activityLog;

    public function __construct(OrganizationInterface $organization, DepartmentInterface $department,ActivityLogInterface $activityLog)
    {
        $this->organization = $organization;
        $this->department = $department;
        $this->activityLog = $activityLog;
    }


    // Display the department dashboard
    public function dashboard()
    {
        return view('department.dashboard');
    }


    // Display the department list
    public function index(Request $request)
    {

        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';
            $filterType = 0;
            $filter = '';
            if ($request->filled('organizationId')) {
                $filterType = 1;
                $filter = $request->organizationId;
            }
            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $data = $this->department->all($search,$filterType,$filter);
            return DataTables::of($data)
                ->addColumn('details', function ($row) {
                    return view('partials.dept_row', ['dept' => $row])->render();
                })
                ->rawColumns(['details'])
                ->make(true);
        }

        $organizations = [];
        $selectedOrganizationId = null;
        $selectedOrganizationName = null;
        if($user->role == 'superadmin') {
            $allOrgs = $this->organization->organization_list();
            $selectedOrganizationId = session(SessionController::SESSION_KEY) ?? $allOrgs->keys()->first();
            // Para o formulário: apenas a empresa selecionada no seletor global (não combo com todas)
            if ($selectedOrganizationId && isset($allOrgs[$selectedOrganizationId])) {
                $organizations = collect([$selectedOrganizationId => $allOrgs[$selectedOrganizationId]]);
                $selectedOrganizationName = $allOrgs[$selectedOrganizationId];
            } else {
                $organizations = $allOrgs;
            }
        } else {
            $organizations = $user->organization->id;
        }

        // Departamentos para o select de departamento pai (mesma organização)
        $orgIdForParents = $user->role === 'superadmin' ? $selectedOrganizationId : $user->organization_id;
        $parentDepartments = $orgIdForParents
            ? Department::where('organization_id', $orgIdForParents)->where('status', 'active')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('common.department.index', compact('organizations', 'user', 'selectedOrganizationId', 'selectedOrganizationName', 'parentDepartments'));
    }

    // Store a new department
    public function store(Request $request)
    {
        try {


            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'organization_id'   => 'required',
                'parent_id'         => [
                    'nullable',
                    'exists:departments,id',
                    function ($attr, $value, $fail) use ($request) {
                        if ($value && $request->organization_id) {
                            $parent = Department::find($value);
                            if ($parent && (int) $parent->organization_id !== (int) $request->organization_id) {
                                $fail('O departamento pai deve pertencer à mesma empresa.');
                            }
                        }
                    },
                ],
                'internal_id'       => 'nullable|string',
                'location'          => 'nullable|string',
                'description'       => 'nullable|string',
                'operating_start'   => 'nullable|string',
                'operating_end'     => 'nullable|string',
                'status'            => 'required|string',
            ]);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            if (empty(trim($data['internal_id'] ?? ''))) {
                $data['internal_id'] = 'DEPT-' . uniqid();
            }

            $department = $this->department->create($data);

            return $this->sendJsonResponse(1,'Department created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific department details
    public function detail(Request $request, $id) {
        $detail = $this->department->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Department detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing department
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'organization_id'   => 'required',
                'parent_id'         => [
                    'nullable',
                    'exists:departments,id',
                    function ($attr, $value, $fail) use ($request, $id) {
                        if ($value == $id) {
                            $fail('Um departamento não pode ser pai de si mesmo.');
                        }
                        if ($value && $request->organization_id) {
                            $parent = Department::find($value);
                            if ($parent && (int) $parent->organization_id !== (int) $request->organization_id) {
                                $fail('O departamento pai deve pertencer à mesma empresa.');
                            }
                        }
                    },
                ],
                'internal_id'       => 'nullable|string',
                'location'          => 'nullable|string',
                'description'       => 'nullable|string',
                'operating_start'   => 'nullable|string',
                'operating_end'     => 'nullable|string',
                'status'            => 'required|string',
            ]);

            $data['updated_by'] = Auth::id();
            if (empty(trim($data['internal_id'] ?? ''))) {
                unset($data['internal_id']);
            }
            $this->department->update($id, $data);

            return $this->sendJsonResponse(1,'Department updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete a department
    public function destroy($id)
    {
        $this->department->delete($id);
        return $this->sendJsonResponse(1,'Department deleted successfully.');
    }

    // List departments by company ID (ordem em árvore + rótulo indentado para selects)
    public function department_list_by_company($id) {

        $departments = Department::forSelectHierarchical((int) $id);

        if ($departments->isNotEmpty()) {
            return $this->sendJsonResponse(1, 'Department list retrieved successfully.', $departments->values()->all());
        }
        return $this->sendJsonResponse(0, 'No data found!');
    }
}
