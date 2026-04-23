<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Models\Dock;
use App\Repositories\Interfaces\DockInterface;
use DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\ActivityLogInterface;
use App\Models\MqttTopic;

class DockRepository implements DockInterface
{

    protected $activityLog;

    public function __construct(ActivityLogInterface $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    // Retrieve all docks with search and filters
    public function all($search, $filterType, $filter, $user, $organizationId = null)
    {
        $query = Dock::select('id','department_id','name','location','capacity','status','dock_number','created_at')
        ->with(['department:id,name,organization_id', 'department.organization:id,name'])
        ->withCount(['devices','activeAvailableDevices'])
        ->orderBy('id','desc');

        if ($user->role === 'superadmin' && $organizationId) {
            $query->whereHas('department', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        } elseif ($user->role == 'admin') {
            $departmentIds = $user->organization?->departments()->pluck('id');
            $query->whereIn('department_id', $departmentIds ?? []);
        } elseif ($user->role == 'manager') {
            $query->where('department_id', $user->department_id);
        }


        if(!empty($search)) {
            $searchNorm = str_replace([':', '-', ' '], '', strtolower($search));
            $query->where(function ($q) use ($search, $searchNorm) {
            $q->where('name', 'like', "%$search%")
                ->orWhere('dock_number', 'like', "%$search%")
                ->orWhere('dock_number', 'like', "%$searchNorm%")
                ->orWhere('status', 'like', "%$search%")
                ->orWhere('capacity', 'like', "%$search%")
                ->orWhere('location', 'like', "%$search%")
                ->orWhereDate('created_at', 'like', "%$search%")
                 ->orWhereHas('department', function ($deptQuery) use ($search) {
                    $deptQuery->where('name', 'like', "%{$search}%");
                });
            });
        }
        if($filterType == 1 ) {
            $query->where('status', $filter);
        }
        $dock = $query->get();

        return $dock;
    }

    // Retrieve specific dock details
    public function detail($id)
    {
        $dock = Dock::select('id','department_id','name','location','capacity','status','dock_number','description','mqtt_topic_id','pairing_code')
        ->with('department.organization')
        ->where('id',$id)->first();

        return $dock;
    }

    // Create a new dock
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $dock = Dock::create($data);

            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $dock->department->organization_id,
                'department_id'   => $dock->department_id,
                'action'          => 'CREATE',
                'entity'          => 'Dock',
                'description'     => 'Created Dock',
                'ip_address'      => request()->ip(),
                'created_by'      => $data['created_by'],
                'updated_by'      => $data['updated_by'],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Dock creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $dock;

    }

    // Update an existing dock
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $dock = Dock::findOrFail($id);
            $dock->update($data);


            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $dock->department->organization_id,
                'department_id'   => $dock->department_id,
                'action'          => 'UPDATE',
                'entity'          => 'Dock',
                'description'     => 'Updated Dock',
                'ip_address'      => request()->ip(),
                'created_by'      => $data['updated_by'],
                'updated_by'      => $data['updated_by'],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Dock updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $dock;
    }

    // Delete a dock
    public function delete($id,$authId)
    {
        DB::beginTransaction();
        try {

            $dock = Dock::findOrFail($id);


            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $dock->department->organization_id,
                'department_id'   => $dock->department_id,
                'action'          => 'DELETE',
                'entity'          => 'Dock',
                'description'     => 'Deleted Dock',
                'ip_address'      => request()->ip(),
                'created_by'      => $authId,
                'updated_by'      => $authId,
            ]);

            $dock->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Dock failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Retrieve statistics for docks (available, inuse, maintenance)
    public function dockStats($user)
    {
        $query = Dock::query();

        if ($user->role === 'admin') {
            $query->whereHas('department.organization', function ($q) use ($user) {
                $q->where('id', $user->organization_id);
            });
        } elseif ($user->role === 'manager') {
            $query->where('department_id', $user->department_id);
        }

        $counts = $query
        ->selectRaw("
            SUM(CASE WHEN status = 'active' AND dock_status = 'available' THEN 1 ELSE 0 END) as available_count,
            SUM(CASE WHEN status = 'active' AND dock_status = 'inuse' THEN 1 ELSE 0 END) as inuse_count,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count
        ")->first();

        $stats = new \stdClass();
        $stats->available_count   = (int) $counts->available_count;
        $stats->inuse_count       = (int) $counts->inuse_count;
        $stats->maintenance_count = (int) $counts->maintenance_count;

        return $stats;
    }


    // Retrieve available MQTT topics from local DB
    public function get_mqtt_topics($dock_id)
    {
        $existingTopicIds = Dock::where('id', '!=', $dock_id)
            ->whereNotNull('mqtt_topic_id')
            ->pluck('mqtt_topic_id')
            ->toArray();

        return MqttTopic::where('is_active', true)
            ->whereNotIn('id', $existingTopicIds)
            ->get()
            ->map(fn($topic) => [
                'id' => $topic->id,
                'name' => $topic->name,
                'description' => $topic->description ?? '',
            ])
            ->values()
            ->all();
    }
}
