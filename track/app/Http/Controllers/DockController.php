<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\Interfaces\DockInterface;
use App\Repositories\Interfaces\DepartmentInterface;
use App\Models\Dock;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\SessionController;

class DockController extends Controller
{
    protected $organization;
    protected $dock;
    protected $department;

    public function __construct(OrganizationInterface $organization, DepartmentInterface $department, DockInterface $dock)
    {
        $this->organization = $organization;
        $this->department = $department;
        $this->dock = $dock;
    }

    // Display the dock list
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';
            $filterType = 0;
            $filter = '';
            if ($request->filled('status')) {
                $filterType = 1;
                $filter = $request->status;
            }
            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $organizationId = null;
            if ($user->role === 'superadmin') {
                $organizationId = $request->filled('organization_id')
                    ? (int) $request->organization_id
                    : session(SessionController::SESSION_KEY);
            }

            $data = $this->dock->all($search, $filterType, $filter, $user, $organizationId);
            return DataTables::of($data)
                ->addColumn('organizationName', function ($row) {
                    return $row->department?->organization?->name ?? '-';
                })
                ->addColumn('mac_address', function ($row) {
                    $num = $row->dock_number ?? '';
                    if (!$num || strlen($num) !== 12) return '-';
                    $num = str_replace([':', '-', ' '], '', strtolower($num));
                    if (strlen($num) !== 12 || !ctype_xdigit($num)) return '-';
                    return strtoupper(implode(':', str_split($num, 2)));
                })
                ->addColumn('departmentName', function ($row) {
                    return $row->department?->name ?? '-';
                })
                ->addColumn('usage_capacity', function ($row) {
                    return '<span>'. $row->devices_count.' / '. $row->capacity .'</span>';
                })
                ->editColumn('status', function ($row) {
                    $badgeClass = match (strtoupper($row->status)) {
                        'ACTIVE' => 'badge-success',
                        'INACTIVE' => 'badge-warning',
                        'MAINTENANCE' => 'badge-danger',
                        default  => 'badge-secondary',
                    };
                    return '<span class="badge '.$badgeClass.' rounded-4">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('status_2', function ($row) {
                    return ucfirst($row->status);
                })
                ->editColumn('active_available_devices_count', function ($row) {
                    return '<span>'. $row->active_available_devices_count . ' devices </span>';
                })
                ->addColumn('active_available_devices_count_2', function ($row) {
                    return $row->active_available_devices_count;
                })
                ->editColumn('action', function ($row) {
                    return
                    '<div class="dropdown">
                        <button type="button" class="btn btn-link dropdown-toggle btn-icon"
                            data-toggle="dropdown">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right rounded-4 p-0">
                            <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="getDockDetail('.$row->id.');">
                                <i class="fa fa-edit"></i>
                                Edit
                            </a>
                            <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="deleteDock('.$row->id.');">
                                <i class="text-danger fa fa-trash"></i>
                                Delete
                            </a>
                        </div>
                    </div>';
                })
                ->rawColumns(['action','status','active_available_devices_count','usage_capacity'])
                ->make(true);
        }

        $organizations = [];
        $selectedOrganizationId = null;
        if($user->role == 'superadmin') {
            $organizations = $this->organization->organization_list();
            $selectedOrganizationId = session(SessionController::SESSION_KEY) ?? $organizations->keys()->first();
        } else {
            $organizations = $user->organization_id;
        }

        if($user->role == 'admin') {
            $departments = $this->department->departmentsByCompanyId($user->organization_id);
        } else {
            $departments = $user->department_id;
        }

        return view('common.docks.manage.index',compact('organizations','user','departments','selectedOrganizationId'));
    }

    // Display the dock panel view
    public function panel()
    {
        $user = Auth::user();

        $query = Dock::select('id','name');
        if($user->role == 'admin') {
            $query->whereHas('department', function($q) use($user) {
                $q->where('organization_id', $user->organization_id);
            });
        } else if($user->role == 'manager') {
            $query->where('department_id',$user->department_id);
        }

        $docks = $query->get();

        $dockStats = $this->dock->dockStats($user);

        return view('common.docks.panel.index',compact('docks','user','dockStats'));
    }


    // Store a new dock
    public function store(Request $request)
    {
        try {


            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'department_id'     => 'required',
                'capacity'          => 'nullable',
                'location'          => 'nullable|string',
                'dock_number'       => 'nullable|string',
                'description'       => 'nullable|string',
                'status'            => 'required|string',
                'active_device'     => 'required',
            ]);

            $data['mqtt_topic_id'] = $data['active_device'];
            unset($data['active_device']);

            $data['pairing_code'] = \App\Services\PendingDeviceActivationService::generatePairingCode();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $dock = $this->dock->create($data);

            return $this->sendJsonResponse(1,'Dock created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific dock details
    public function detail(Request $request, $id) {
        $detail = $this->dock->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Dock detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing dock
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'department_id'     => 'required',
                'capacity'          => 'nullable',
                'location'          => 'nullable|string',
                'dock_number'       => 'nullable|string',
                'description'       => 'nullable|string',
                'status'            => 'required|string',
                'active_device'     => 'required',
            ]);

            $data['mqtt_topic_id'] = $data['active_device'];
            unset($data['active_device']);

            $data['updated_by'] = Auth::id();
            $this->dock->update($id, $data);

            return $this->sendJsonResponse(1,'Dock updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete a dock
    public function destroy($id)
    {
        $authId = Auth::user()->id;
        $this->dock->delete($id,$authId);
        return $this->sendJsonResponse(1,'Dock deleted successfully.');
    }

    // Regenerar código de pareamento do tablet
    public function regeneratePairing(Request $request, $id)
    {
        $dock = Dock::findOrFail($id);
        $dock->pairing_code = \App\Services\PendingDeviceActivationService::generatePairingCode();
        $dock->save();
        return $this->sendJsonResponse(1, 'Código regenerado.', ['pairing_code' => $dock->pairing_code]);
    }

    // Retrieve MQTT topics for a dock
    public function get_mqtt_topics(Request $request,$dock_id) {
        $detail = $this->dock->get_mqtt_topics($dock_id);

        return $this->sendJsonResponse(1,'MQTT topics retrieved successfully.',$detail);

    }
}
