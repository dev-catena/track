<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\ActivityLogInterface;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\Interfaces\DepartmentInterface;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{

    protected $activityLog;
    public function __construct(ActivityLogInterface $activityLog, OrganizationInterface $organization, DepartmentInterface $department)
    {
        $this->activityLog = $activityLog;
        $this->organization = $organization;
        $this->department = $department;
    }


    // Display the activity logs listing
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';

            $organization_id = $request->organization;
            $department_id = $request->department;
            $action = $request->action;
            $entity = $request->entity;

            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $data = $this->activityLog->all($search,$entity,$action,$user,$organization_id,$department_id);
            return DataTables::of($data)
                ->addIndexColumn()
                // ->editColumn('created_at', function ($row) {
                //     return Carbon::parse($row->created_at)->format('d/m/Y, H:i:s');
                // })
                ->editColumn('action', function ($row) {
                    $badgeClass = match (strtoupper($row->action)) {
                        'CREATE'   => 'badge-success',
                        'UPDATE'   => 'badge-warning',
                        'DELETE'   => 'badge-danger',
                        'CHECKIN'  => 'badge-info',
                        'CHECKOUT' => 'badge-primary',
                        default    => 'badge-secondary',
                    };
                    return '<span class="badge '.$badgeClass.' rounded-4">'.ucfirst($row->action).'</span>';
                })
                ->editColumn('createdBy', function ($row) {
                    if (in_array($row->action, ['CREATE', 'UPDATE', 'DELETE'])) {
                        return $row->createdBy?->email ?? 'N/A';
                    } else {
                        return $row->createdByOperator?->email ?? 'N/A';
                    }
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $data = $this->activityLog->cardData($user);
        $role = $user->role;

        $organizations = [];
        if($role == 'superadmin'){
            $organizations = $this->organization->organization_list();
        }
        $departments = [];
        if($role == 'admin') {
            $departments = $this->department->departmentsByCompanyId($user->organization_id);
        }
        return view('common.logs.index',compact('data','role','organizations','departments'));
    }
}
