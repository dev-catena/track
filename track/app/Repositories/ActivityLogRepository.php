<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\ActivityLogInterface;
use DB;
use Illuminate\Support\Facades\Log;


class ActivityLogRepository implements ActivityLogInterface
{

    // Retrieve all activity logs with filtering options
    public function all($search, $entity, $action,$user,$organization_id,$department_id)
    {
        $query = ActivityLog::select('id','organization_id','department_id','action','entity','description','ip_address','created_at','created_by')
        ->with(['createdBy:id,email']);
        if($user->role == 'admin') {
            $query->where('organization_id',$user->organization_id);
        } else if($user->role == 'manager') {
            $query->where('department_id',$user->department_id);
        }
        $query->orderBy('id','desc');

        if(!empty($search)) {
            $query->where(function ($q) use ($search) {
            $q->where('action', 'like', "%$search%")
                ->orWhere('entity', 'like', "%$search%")
                ->orWhere('ip_address', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhereDate('created_at', 'like', "%$search%")
                ->orWhereHas('createdBy', function ($orgQuery) use ($search) {
                    $orgQuery->where('email', 'like', "%{$search}%");
                });
            });
        }


        if($organization_id) {
            $query->where('organization_id', $organization_id);
        }
        if($department_id) {
            $query->where('department_id', $department_id);
        }
        if($entity) {
            $query->where('entity', $entity);
        }

        if($action) {
            $query->where('action', $action);
        }
        $activityLog = $query->get();

        return $activityLog;
    }
    // Create a new activity log entry
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $log = ActivityLog::create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('activity creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $log;

    }

    // Retrieve activity log statistics (counts)
    public function cardData($user)
    {

        $query = ActivityLog::query();

        if ($user->role === 'admin') {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->role === 'manager') {
            $query->where('department_id', $user->department_id);
        }

        $today = now()->toDateString();

        $counts = $query->selectRaw('
            COUNT(id) as total_activities_count,
            SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as todays_activities_count,
            SUM(CASE WHEN entity = "dock" THEN 1 ELSE 0 END) as dock_action_counts,
            SUM(CASE WHEN entity = "device" THEN 1 ELSE 0 END) as device_action_counts
        ', [$today])->first();

        $summary = new \stdClass();
        $summary->total_activities_count = (int) $counts->total_activities_count;
        $summary->todays_activities_count = (int) $counts->todays_activities_count;
        $summary->dock_action_counts     = (int) $counts->dock_action_counts;
        $summary->device_action_counts   = (int) $counts->device_action_counts;

        return $summary;
    }

}
