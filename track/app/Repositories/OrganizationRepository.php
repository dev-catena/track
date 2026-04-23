<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Models\Dock;
use App\Models\Department;
use App\Models\Device;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\Interfaces\ConfigurationInterface;
use App\Repositories\Interfaces\ActivityLogInterface;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Support\Facades\Password;
use DB;
use Illuminate\Support\Facades\Log;
class OrganizationRepository implements OrganizationInterface
{

    protected $configuration;
    protected $user;
    protected $activityLog;

    public function __construct(ConfigurationInterface $configuration, UserInterface $user, ActivityLogInterface $activityLog)
    {
        $this->configuration = $configuration;
        $this->user = $user;
        $this->activityLog = $activityLog;
    }

    // Retrieve all organizations with search and filters
    public function all($search, $filterType, $filter)
    {
        $query = Organization::select('id','name','email','phone','address','city','state','plan_id','max_devices','created_at')
        ->with(['plan:id,name'])
        ->withCount([
            'docks as dock_count',
            'users as user_count',
            'operators as operator_count',
        ])
        ->addSelect([
            'device_count' => \DB::table('devices')
                ->join('docks', 'devices.dock_id', '=', 'docks.id')
                ->join('departments', 'docks.department_id', '=', 'departments.id')
                ->whereColumn('departments.organization_id', 'organizations.id')
                ->whereNull('devices.deleted_at')
                ->whereNull('docks.deleted_at')
                ->whereNull('departments.deleted_at')
                ->selectRaw('count(devices.id)'),
        ])
        //->where('status','active')
        ->orderBy('id','desc');

        if(!empty($search)) {
            $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
                ->orWhere('max_devices', 'like', "%$search%")
                ->orWhere('address', 'like', "%$search%")
                //->orWhere('city', 'like', "%$search%")
                //->orWhere('state', 'like', "%$search%")
                ->orWhereDate('created_at', 'like', "%$search%")
                ->orWhereHas('plan', function ($planQuery) use ($search) {
                    $planQuery->where('name', 'like', "%{$search}%");
                });
            });
        }
        if($filterType == 1 ) {
            $query->where('plan_id', $filter);
        }
        $organization = $query->get();

        return $organization;
    }

    // Retrieve specific organization details
    public function detail($id)
    {
        $organization = Organization::select('id','name','email','phone','address','city','state','plan_id','max_devices','created_at','mdm','cnpj','status')
        ->with(['plan:id,name'])->where('id',$id)->first();

        return $organization;
    }

    // Create a new organization and sync with MQTT
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $organization = Organization::create($data);

            //set the theme
            $configuration = $this->configuration->create($organization);

            // Create the organization admin user
            $org_admin = $this->user->createOrganizationAdmin($organization);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $organization;

    }

    // Update an existing organization and sync with MQTT
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $organization = Organization::findOrFail($id);
            $organization->update($data);

            //$admin = $this->user->updateOrganizationAdmin($organization);


            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $organization;
    }

    // Delete an organization and sync with MQTT
    public function delete($id)
    {
        DB::beginTransaction();
        try {

            $organization = Organization::findOrFail($id);

            $organization->delete();
            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Retrieve a list of active organizations
    public function organization_list()
    {
        $organizations = Organization::where('status','active')
        ->orderBy('id','desc')
        ->pluck('name','id');

        return $organizations;
    }

    // Retrieve organization dashboard statistics
    public function org_dashboard_stats($orgId)
    {
        $org = Organization::withCount([
            'departments as total_departments',
            'departments as active_departments' => fn($q) => $q->where('status', 'active'),
        ])->findOrFail($orgId);

        // Docks
        $docksQuery = Dock::whereHas('department', fn($q) => $q->where('organization_id', $orgId));
        $totalDocks   = $docksQuery->count();
        $activeDocks  = (clone $docksQuery)->where('status', 'active')->count();


        // Devices
        $devicesQuery = Device::whereHas('dock.department', fn($q) => $q->where('organization_id', $orgId));
        $totalDevices   = $devicesQuery->count();
        $activeDevices  = (clone $devicesQuery)->where('status', 'active')->count();
        $availableDevices  = (clone $devicesQuery)->where([['status', 'active'],['device_status','available']])->count();
        $inuseDevices   = (clone $devicesQuery)->where('device_status', 'inuse')->count();
        $overdueDevices = (clone $devicesQuery)->where(function ($q) {
            $q->where('device_status', 'overdue')
            ->orWhere('status', 'maintenance');
        })->count();

        return (object) [
            'total_departments'  => $org->total_departments,
            'active_departments' => $org->active_departments,
            'total_docks'        => $totalDocks,
            'active_docks'       => $activeDocks,
            'total_devices'      => $totalDevices,
            'active_devices'     => $activeDevices,
            'available_devices'  => $availableDevices,
            'inuse_devices'      => $inuseDevices,
            'overdue_devices'    => $overdueDevices,
        ];
    }


    // Retrieve dock statistics for organization dashboard
    public function dockGraphStats($orgId)
    {
        $data = Dock::whereHas('department', function ($q) use ($orgId) {
            $q->where([['organization_id', $orgId],['status','active']]);
        })
        ->where('status','active')
        ->select('id', 'name')
        ->withCount([
            // Device counts directly in SQL
            'devices as available_devices' => function ($q) {
                $q->where('status', 'active')
                ->where('device_status', 'available');
            },
            'devices as inuse_devices' => function ($q) {
                $q->where('device_status', 'inuse');
            },
            'devices as overdue_devices' => function ($q) {
                $q->whereIn('device_status', ['overdue'])
                ->orWhere('status', 'maintenance');
            },
        ])
        ->get();

        return $data;
    }

    // Retrieve department statistics for organization dashboard
    public function departmentGraphStats($orgId)
    {
        $data = Department::where('organization_id', $orgId)
        ->where('departments.status', 'active')
        ->select('id','name')
        ->withCount([
            // Devices through docks relation
            'devices as available_devices' => function ($q) {
                $q->where('devices.status', 'active')
                   ->where('devices.device_status', 'available');
            },
            'devices as inuse_devices' => function ($q) {
                $q->where('devices.device_status', 'inuse');
            },
            'devices as overdue_devices' => function ($q) {
                $q->whereIn('devices.device_status', ['overdue'])
                  ->orWhere('devices.status', 'maintenance');
            },
        ])
        ->get();

        return $data;
    }

}
