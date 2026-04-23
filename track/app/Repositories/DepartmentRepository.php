<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Models\Department;
use App\Repositories\Interfaces\DepartmentInterface;
use App\Repositories\Interfaces\ConfigurationInterface;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Support\Facades\Password;
use DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\ActivityLogInterface;
class DepartmentRepository implements DepartmentInterface
{

    protected $configuration;
    protected $user;
    protected $activityLog;
    public function __construct(ConfigurationInterface $configuration, UserInterface $user,ActivityLogInterface $activityLog)
    {
        $this->configuration = $configuration;
        $this->user = $user;
        $this->activityLog = $activityLog;
    }

    // Retrieve all departments with search and filter
    public function all($search, $filterType, $filter)
    {
        $query = Department::select('id','organization_id','parent_id','internal_id','name','location','operating_start','operating_end','description','created_at')
        ->with(['organization:id,name', 'parent:id,name'])
        ->withCount([
            'docks as dock_count',
            'devices as device_count',
        ])
        //->where('status','active')
        ->orderByRaw('COALESCE(parent_id, id) ASC, CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END ASC, name ASC');

        if(!empty($search)) {
            $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
                ->orWhere('internal_id', 'like', "%$search%")
                ->orWhere('location', 'like', "%$search%")
                ->orWhere('operating_start', 'like', "%$search%")
                ->orWhere('operating_end', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhereDate('created_at', 'like', "%$search%")
                ->orWhereHas('organization', function ($orgQuery) use ($search) {
                    $orgQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('parent', function ($pQuery) use ($search) {
                    $pQuery->where('name', 'like', "%{$search}%");
                });
            });
        }
        if($filterType == 1 ) {
            $query->where('organization_id', $filter);
        }
        $department = $query->get();

        return $department;
    }

    // Retrieve specific department details
    public function detail($id)
    {
        $department = Department::select('id','organization_id','parent_id','internal_id','name','location','operating_start','operating_end','description','created_at','status')
        ->with(['organization:id,name', 'parent:id,name'])
        ->where('id',$id)->first();

        return $department;
    }

    // Create a new department and sync with MQTT
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $department = Department::create($data);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Department creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $department;

    }

    // Update an existing department and sync with MQTT
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $department = Department::findOrFail($id);
            $department->update($data);


            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Department updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $department;
    }

    // Delete a department and sync with MQTT
    public function delete($id)
    {
        DB::beginTransaction();
        try {

            $department = Department::findOrFail($id);


            $department->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Department failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // List departments by company ID
    public function departmentsByCompanyId($id) {
        $organization = Organization::findOrFail($id);

        $departments = $organization->departments()
        ->where('status', 'active')
        ->orderBy('id', 'desc')
        ->get(['id', 'name']);
        return $departments;
    }


}
