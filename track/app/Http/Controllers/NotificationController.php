<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Notification;
use DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    // Display system notifications
    public function index(Request $request)
    {

        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';

            $entity = $request->entity;
            $action = $request->action;

            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $query = Notification::select('id','device_id','type','description','operator_id','status','created_at')
            ->with([
                'operator:id,email',
                'device:id,name,dock_id',
                'device.dock:id,department_id',
                'device.dock.department:id,organization_id,name',
                'device.dock.department.organization:id,name',
            ]);
            if($user->role == 'admin') {
                $query->whereHas('device.dock.department.organization', function ($q) use ($user) {
                    $q->where('organization_id', $user->organization_id);
                });
            } else if($user->role == 'manager') {
                $query->whereHas('device.dock.department', function ($q) use ($user) {
                    $q->where('id', $user->department_id);
                });
            }
            $query->orderBy('id','desc');

            if(!empty($search)) {
                $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhereDate('created_at', 'like', "%$search%")
                    ->orWhereHas('operator', function ($oprQuery) use ($search) {
                        $oprQuery->where('email', 'like', "%{$search}%");
                    });
                });
            }
            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                // ->editColumn('created_at', function ($row) {
                //     return Carbon::parse($row->created_at)->format('d/m/Y, H:i:s');
                // })
                ->editColumn('status', function ($row) {
                    $badgeClass = match (strtolower($row->status)) {
                        'resolved'   => 'badge-success',
                        'pending'   => 'badge-warning',
                        default    => 'badge-secondary',
                    };
                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->status).'</span>';
                })
                ->editColumn('device', function ($row) {
                        return $row->device?->name ?? 'N/A';

                })
                ->editColumn('operator', function ($row) {
                        return $row->operator?->email ?? 'N/A';

                })
                ->editColumn('type', function ($row) {
                    return ucfirst($row->type);
                })
                ->rawColumns(['status','description'])
                ->make(true);
        }
        return view('common.notification.index');
    }
}
