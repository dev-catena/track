<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\DockInterface;
use App\Repositories\Interfaces\DeviceInterface;
use App\Models\Dock;
use App\Models\Device;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{
    protected $dock;
    protected $device;

    public function __construct(DockInterface $dock,DeviceInterface $device)
    {
        $this->dock = $dock;
        $this->device = $device;
    }


    // Display the device list
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';
            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }

            $data = $this->device->all($search,$request->status,$request->dock,$user);
            return DataTables::of($data)
                ->addIndexColumn()
                // ->editColumn('created_at', function ($row) {
                //     return Carbon::parse($row->created_at)->format('d/m/Y');
                // })
                ->addColumn('dockName', function ($row) {
                    return $row->dock
                        ? '<span>'.$row->dock->name . '<br> (' . $row->dock->location . ') </span>'
                        : '';
                })
                ->addColumn('display_status', function ($row) {
                    return $row->display_status;
                })
                // ->editColumn('return_date', function ($row) {
                // return $row->return_date
                //     ? Carbon::parse($row->return_date)->format('Y-m-d H:i:s')
                //     : '-';
                // })
                ->editColumn('action', function ($row) {
                    return
                    '<div class="dropdown">
                        <button type="button" class="btn btn-link dropdown-toggle btn-icon"
                            data-toggle="dropdown">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right rounded-4 p-0">
                            <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="getDeviceDetail('.$row->id.');">
                                <i class="fa fa-edit"></i>
                                Edit
                            </a>
                            <a class="dropdown-item m-0 rounded-4" href="javascript:void(0);" onclick="deleteDevice('.$row->id.');">
                                <i class="text-danger fa fa-trash"></i>
                                Delete
                            </a>
                        </div>
                    </div>';
                })

                ->rawColumns(['display_status','status','dockName','action'])
                ->make(true);
        }

        $query = Dock::select('id','name')->where('status','active');
        if($user->role == 'admin') {
            $query->whereHas('department', function($q) use($user) {
                $q->where('organization_id', $user->organization_id);
            });
        } else if($user->role == 'manager') {
            $query->where('department_id',$user->department_id);
        }

        $docks = $query->get();

        return view('common.device.index',compact('docks','user'));
    }

    // Store a new device
    public function store(Request $request)
    {
        try {


            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'dock_id'           => 'required',
                'model_name'        => 'required',
                'serial_number'     => 'nullable|string',
                'tag_id'            => 'nullable|string',
                'description'       => 'nullable|string',
                'status'            => 'required|string',
            ]);


            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            //check for remaining capacity for device in dock
            $dock = Dock::find($data['dock_id']);

            // Already assigned devices count
            $currentCount = $dock->devices()->count();

            // Check remaining capacity
            if ($currentCount >= $dock->capacity) {
                return $this->sendJsonResponse(0, 'Dock has reached its maximum capacity.');
            }

            $device = $this->device->create($data);

            return $this->sendJsonResponse(1,'Device created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific device details
    public function detail(Request $request, $id) {
        $detail = $this->device->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Device detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing device
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'dock_id'           => 'required',
                'model_name'        => 'required',
                'serial_number'     => 'nullable|string',
                'tag_id'            => 'nullable|string',
                'description'       => 'nullable|string',
                'status'            => 'required|string',
            ]);

            $data['updated_by'] = Auth::id();


            $this->device->update($id, $data);

            return $this->sendJsonResponse(1,'Device updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete a device
    public function destroy($id)
    {
        $authId = Auth::user()->id;
        $this->device->delete($id,$authId);
        return $this->sendJsonResponse(1,'Device deleted successfully.');
    }


}
