<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\OrganizationInterface;
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

class OrganizationController extends Controller
{
    protected $organization;

    public function __construct(OrganizationInterface $organization)
    {
        $this->organization = $organization;
    }

    // Display the organization dashboard
    public function dashboard()
    {
        $user = Auth::user();

        $org_stats = $this->organization->org_dashboard_stats($user->organization_id);


        return view('organization.dashboard',compact('org_stats','user'));
    }


    // Retrieve dashboard graph statistics
    public function dashboardGraphStats(Request $request) {
        $orgId = Auth::user()->organization_id;
        $dock = $this->organization->dockGraphStats($orgId);
        $department = $this->organization->departmentGraphStats($orgId);

        $data = [];
        if(!($dock->isEmpty())) {
            $data['dock'] = $dock;
        }
        if(!($department->isEmpty())) {
            $data['department'] = $department;
        }
        if(empty($data)) {
            return $this->sendJsonResponse(1,'Data not exists.');
        }
        return $this->sendJsonResponse(1,'Dashboard graph stats retrieved successfully.',$data);

    }



    // List all organizations/companies
    public function index(Request $request)
    {

        if ($request->ajax() || $request->has('draw')) {

            $search = '';
            $filterType = 0;
            $filter = '';
            if ($request->filled('planId')) {
                $filterType = 1;
                $filter = $request->planId;
            }
            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $data = $this->organization->all($search,$filterType,$filter);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('details', function ($row) {
                    return view('partials.org_card', ['org' => $row])->render();
                })
                ->rawColumns(['details'])
                ->make(true);
        }
        $plans = Plan::where('status','active')->orderBy('id','asc')->pluck('name','id');
        return view('superadmin.organization.index', compact('plans'));
    }

    // Store a new organization/company
    public function store(Request $request)
    {
        try {


            $data = $request->validate([
                'name'          => 'required|string|max:255|unique:organizations,name',
                'email'         => 'required|string|unique:organizations,email',
                'phone'         => 'nullable|string',
                'address'       => 'nullable|string',
                'cnpj'          => 'nullable|string',
                'city'          => 'nullable|string',
                'state'         => 'nullable|string',
                'plan_id'       => 'nullable|integer',
                'max_devices'   => 'nullable|integer',
                'mdm'           => 'nullable|string',
                'status'        => 'required|string',
            ]);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();


            //check if the same email exists in the users table
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                return $this->sendJsonResponse(0, 'Email already taken.');
            }

            $organization = $this->organization->create($data);

            return $this->sendJsonResponse(1,'Company created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific organization details
    public function detail(Request $request, $id) {
        $detail = $this->organization->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Company detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing organization
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'          => 'required|string|max:255|unique:organizations,name,' . $id,
                'email'         => 'required|string|email|unique:organizations,email,' . $id,
                'phone'         => 'nullable|string',
                'address'       => 'nullable|string',
                'cnpj'          => 'nullable|string',
                'city'          => 'nullable|string',
                'state'         => 'nullable|string',
                'plan_id'       => 'nullable|integer',
                'max_devices'   => 'nullable|integer',
                'mdm'           => 'nullable|string',
                'status'        => 'required|string',
            ]);

            //check if the same email exists in the users table
            $existingUser = User::where([['email', $data['email']],['organization_id','!=',$id]])->first();
            if ($existingUser) {
                return $this->sendJsonResponse(0, 'Email already taken.');
            }

            $data['updated_by'] = Auth::id();
            $this->organization->update($id, $data);

            return $this->sendJsonResponse(1,'Company updated successfully.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete an organization
    public function destroy($id)
    {
        $this->organization->delete($id);
        return $this->sendJsonResponse(1,'Company deleted successfully.');
    }


}
