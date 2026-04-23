<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use stdClass;
use App\Repositories\Interfaces\DeviceInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;
use App\Services\ThalamusFaceService;
use App\Helpers\BotConfigHelper;
use App\Models\AuditHistory;
use App\Models\Organization;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperatorApiController extends Controller
{

    public function __construct(DeviceInterface $device)
    {
        $this->device = $device;
    }



    // Display operator dashboard data
    public function dashboard(Request $request) {

        $operator = $request->user();

        $deviceData = new stdClass();
        $data = new stdClass();

        $lastAudit = AuditHistory::where('operator_id', $operator->id)
        ->latest('id')
        ->first();
        if ($lastAudit && $lastAudit->audit_type === 'check_out') {

            $device = Device::findOrFail($lastAudit->device_id);

            $maxCheckoutHours = BotConfigHelper::getMaxCheckoutHours();

            $checkoutTime = Carbon::parse($lastAudit->audit_out_time);

            $deadline     = $checkoutTime->copy()->addHours($maxCheckoutHours);
            $checkInTimeLeft = (int) max(0, now()->diffInSeconds($deadline, false));

            $deviceData->id = $device->id;
            $deviceData->name = $device->name ?? '';
            $deviceData->device_status = $device->device_status;
            $deviceData->checked_out_time = $lastAudit->audit_out_time_format_time;
            $deviceData->check_in_time_left = $checkInTimeLeft;

            $data->checked_in = 1;
        } else {
            $data->checked_in = 0;
        }

        $lastCheckout = AuditHistory::where([['operator_id', $operator->id],['audit_type','check_out']])
        ->latest('id')
        ->first();

        $data->device = $deviceData;
        $dashOrgName = $operator->organization_id
            ? (string) Organization::where('id', $operator->organization_id)->value('name')
            : '';
        $data->user = (object) [
            'id'                            => $operator->id,
            'name'                          => $operator->name,
            'email'                         => $operator->email,
            'username'                      => $operator->username,
            'organization_id'               => $operator->organization_id,
            'department_id'                 => $operator->department_id,
            'organization_name'             => $dashOrgName,
            'phone'                         => $operator->phone ?? '',
            'address'                       => $operator->address ?? '',
            'shift_type'                    => $operator->shift_type,
            'status'                        => $operator->status,
            'last_picked_device_name'       => $lastCheckout ? optional($lastCheckout->device)->name : '',
            'last_picked_device_datetime'   => $lastCheckout ? $lastAudit->audit_out_time_format_datetime : '',
        ];


        return $this->sendJsonResponse(1, 'Data retrieved successfully!', $data);
    }


    // Capture device location
    public function deviceLocationCapture(Request $request) {
        try {

            $data = $request->validate([
                'device_id' => 'required|exists:devices,id',
                'latitude'  => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'logged_at' => 'nullable|date',
            ]);

            $resp = $this->device->captureDeviceLocation($data);

            return $this->sendJsonResponse(1, 'Device location captured successfully!', $resp);

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }


    // Device check-in
    public function deviceCheckin(Request $request) {


        try {

            $operator = $request->user();
            $authId = $operator->id;

            $data = $request->validate([
                'device_id' => 'required|exists:devices,id',
                'latitude'  => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            $response = $this->device->checkin($data['device_id'],$authId,$request->latitude,$request->longitude,$operator->fcm_token);

            return $this->sendJsonResponse($response->status,$response->message);


        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Mock checkout - bypassa validação facial (para desenvolvimento/testes)
    public function deviceCheckoutMock(Request $request)
    {
        try {
            $operator = $request->user();
            $data = $request->validate([
                'device_serial_number' => 'required|string',
                'latitude'             => 'required|numeric|between:-90,90',
                'longitude'            => 'required|numeric|between:-180,180',
            ]);

            $deviceId = Device::where('serial_number', 'LIKE', "%{$data['device_serial_number']}%")->value('id');
            if (!$deviceId) {
                return $this->sendJsonResponse(0, 'Dispositivo não encontrado com este número de série.');
            }

            $response = $this->device->checkout(
                $deviceId,
                $operator->id,
                $data['latitude'],
                $data['longitude'],
                $operator->fcm_token ?? ''
            );

            if (isset($response->status) && $response->status == 0) {
                return $this->sendJsonResponse(0, $response->message);
            }

            return $this->sendJsonResponse(1, 'Checkout realizado! Dispositivo liberado.', $response);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);
        } catch (Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }

    // Validate user for device checkout
    public function validateUser(Request $request) {
        try {

            $request->validate([
                'image'                 => 'required|file|mimes:jpg,jpeg,png|max:4096',
                'latitude'              => 'required|numeric|between:-90,90',
                'longitude'             => 'required|numeric|between:-180,180',
                'device_serial_number'  => 'required',
            ]);

            //Log::info("Device Serial Number/Build Number = {$request->device_serial_number}");
            $operator = $request->user();

            $thalamus = new ThalamusFaceService;
            if (! $thalamus->isConfigured()) {
                return $this->sendJsonResponse(0, 'Face API not configured.');
            }

            $imagePath = $request->file('image')->getRealPath() ?: $request->file('image')->path();
            $rec = $thalamus->recognizeFromImage($imagePath);

            if (! $rec['ok'] || $rec['face_id'] === null || $rec['face_id'] === '') {
                return $this->sendJsonResponse(0, $rec['message'] ?? 'No match found!');
            }

            if ($operator->face_id && $rec['face_id'] === $operator->face_id) {

                $deviceId = Device::where('serial_number', 'LIKE', "%{$request->device_serial_number}%")->value('id');

                if (!$deviceId) {
                    return $this->sendJsonResponse(0,'Device not found with this build number!');
                }


                $data = $this->device->checkout($deviceId,$operator->id,$request->latitude,$request->longitude,$operator->fcm_token);

                if (isset($data->status) && $data->status == 0) {
                    return $this->sendJsonResponse(0,$data->message);
                }

                return $this->sendJsonResponse(1,'User validated successfully, You can Checkout the device!',$data);
            } else {
                return $this->sendJsonResponse(0,'Invalid user!');
            }

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage() . ' in ' . basename($e->getFile()) . ' on line ' . $e->getLine());
        }
    }

    // Get operator reports (checkouts/checkins)
    public function reports(Request $request)
    {
        $operator = $request->user();

        // Fetch related models efficiently
        $organization = $operator->organization()->select('id', 'name')->first();
        $department   = $operator->department()->select('id', 'name')->first();

        // Fetch checkouts (last 30 days)
        $auditHistories = AuditHistory::with('device:id,name')
        ->where('operator_id', $operator->id)
        ->where('audit_type', 'check_out')
        ->whereBetween('audit_out_time', [
            Carbon::now()->subDays(30)->startOfDay(),
            Carbon::now()->endOfDay(),
        ])
        ->latest('id')
        ->get(['id', 'device_id', 'audit_out_time']);


        // collect all device IDs
        $deviceIds = $auditHistories->pluck('device_id')->unique();

        //fetch all check-ins for these devices by same operator (after 30 days window)
        $checkins = AuditHistory::where('operator_id', $operator->id)
        ->where('audit_type', 'check_in')
        ->whereIn('device_id', $deviceIds)
        ->whereBetween('audit_in_time', [
            Carbon::now()->subDays(30)->startOfDay(),
            Carbon::now()->endOfDay(),
        ])
        ->orderBy('audit_in_time', 'asc')
        ->get(['device_id', 'audit_in_time']);

        //group by device_id for fast lookup
        $checkinByDevice = $checkins->groupBy('device_id');

        // final response
        $data = [
            'organization' => $organization ? [
                'id'   => $organization->id,
                'name' => $organization->name,
            ] : new stdClass(),

            'department' => $department ? [
                'id'   => $department->id,
                'name' => $department->name,
            ] : new stdClass(),

            'reports' => $auditHistories->map(function ($checkout) use ($checkinByDevice) {
                $deviceCheckins = $checkinByDevice->get($checkout->device_id, collect());

                // Find the first checkin after this checkout
                $checkin = $deviceCheckins->first(function ($ci) use ($checkout) {
                    return $ci->audit_in_time > $checkout->audit_out_time;
                });

                $status = $checkin ? 1 : 0;
                $status_message = $checkin ? 'Delivered' : 'Not Delivered';

                return (object) [
                    'device_name'      => optional($checkout->device)->name ?? 'Unknown Device',
                    'last_pickup_date' => $checkout->audit_out_time_format_datetime,
                    'return_date'      => $checkin ? $checkin->audit_in_time_format_datetime : '',
                    'status'           => $status,
                    'status_message'   => $status_message,
                ];
            }),
        ];

        return $this->sendJsonResponse(1, 'Data retrieved successfully!', $data);
    }


}
