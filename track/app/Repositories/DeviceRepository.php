<?php

namespace App\Repositories;

use App\Models\Device;
use App\Models\AuditHistory;
use App\Models\Dock;
use App\Models\Notification;
use App\Models\DeviceLocationLog;
use App\Repositories\Interfaces\DeviceInterface;
use DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\ActivityLogInterface;
use stdClass;
use App\Helpers\BotConfigHelper;
use Carbon\Carbon;
use App\Services\MqttService;
use App\Models\MqttTopic;
use App\Jobs\DeviceDelayNotificationJob;
use App\Jobs\DeviceTheftNotificationJob;
use App\Jobs\DeviceReminderNotificationJob;
use App\Services\FCMService;

class DeviceRepository implements DeviceInterface
{

    protected $activityLog;
    protected $mqttService;

    public function __construct(ActivityLogInterface $activityLog, MqttService $mqttService)
    {
        $this->activityLog = $activityLog;
        $this->mqttService = $mqttService;
    }

    // Retrieve all devices with filtering
    public function all($search, $status, $dock,$user)
    {
        $query = Device::select('id','dock_id','name','serial_number','model_name','status','device_status','created_at','return_date')
        ->with(['dock:id,name,location'])
        ->orderBy('id','desc');

        if($user->role == 'admin') {
            $query->whereHas('dock.department', function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        } else if($user->role == 'manager') {
            $query->whereHas('dock', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }


        if(!empty($search)) {
            $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
                ->orWhere('model_name', 'like', "%$search%")
                ->orWhere('serial_number', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%")
                ->orWhere('device_status', 'like', "%$search%")
                ->orWhere('return_date', 'like', "%$search%")
                ->orWhereDate('created_at', 'like', "%$search%")
                ->orWhereHas('dock', function ($orgQuery) use ($search) {
                    $orgQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%$search%");
                });
            });
        }
        if($status) {
            if($status == 'maintenance') {
                $query->where('status', $status);
            } else {
                $query->where('device_status', $status);
            }
        }
        if($dock) {
            $query->where('dock_id', $dock);
        }
        $device = $query->get();

        return $device;
    }

    // Retrieve specific device details
    public function detail($id)
    {
        $device = Device::select('id','dock_id','name','model_name','serial_number','status','tag_id','description')
        ->where('id',$id)->first();

        return $device;
    }

    // Create a new device and log activity
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $device = Device::create($data);

            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $device->dock->department->organization_id,
                'department_id'   => $device->dock->department_id,
                'action'          => 'CREATE',
                'entity'          => 'Device',
                'description'     => 'Created Device',
                'ip_address'      => request()->ip(),
                'created_by'      => $data['created_by'],
                'updated_by'      => $data['updated_by'],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $device;

    }

    // Update an existing device and log activity
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $device = Device::findOrFail($id);

            if($device->dock_id != $data['dock_id']) {
                $dock = Dock::findOrFail($data['dock_id']);
                // Already assigned devices count
                $currentCount = $dock->devices()->count();

                // Check remaining capacity
                if ($currentCount >= $dock->capacity) {
                    return $this->sendJsonResponse(0, 'Dock has reached its maximum capacity.');
                }
            }

            $device->update($data);


            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $device->dock->department->organization_id,
                'department_id'   => $device->dock->department_id,
                'action'          => 'UPDATE',
                'entity'          => 'Device',
                'description'     => 'Updated Device',
                'ip_address'      => request()->ip(),
                'created_by'      => $data['updated_by'],
                'updated_by'      => $data['updated_by'],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $device;
    }

    // Delete a device and log activity
    public function delete($id,$authId)
    {
        DB::beginTransaction();
        try {

            $device = Device::findOrFail($id);


            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $device->dock->department->organization_id,
                'department_id'   => $device->dock->department_id,
                'action'          => 'DELETE',
                'entity'          => 'Device',
                'description'     => 'Deleted Device',
                'ip_address'      => request()->ip(),
                'created_by'      => $authId,
                'updated_by'      => $authId,
            ]);

            $device->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Device checkout process
    public function checkout($id,$authId,$lat,$long,$fcm_token)
    {
        $data = new stdClass();

        DB::beginTransaction();
        try {


            $lastAudit = AuditHistory::where('operator_id', $authId)
            ->latest('id')->first();

            if ($lastAudit && $lastAudit->audit_type === 'check_out') {
                $data->status = 0;
                $data->message = 'You have already checked out your last device. Cannot checkout again.';

                return $data;
            }

            $lastAuditDevice = AuditHistory::where('device_id', $id)
            ->latest('id')->first();

            if ($lastAuditDevice && $lastAuditDevice->audit_type === 'check_out') {
                $data->status = 0;
                $data->message = 'Device is already in use by another user.';

                return $data;
            }

            $device = Device::findOrFail($id);
            $topicId = $device->dock_mqtt_topic_id;

            if(!$topicId || $topicId == 0) {
                $data->status = 0;
                $data->message = 'Active device is not linked to the device’s dock.';

                return $data;
            }

            $mqttTopic = MqttTopic::find($topicId);
            if (!$mqttTopic || !$mqttTopic->is_active) {
                Log::warning('MQTT: Tópico não encontrado ou inativo', ['topic_id' => $topicId]);
                $data->status = 0;
                $data->message = 'MQTT command failed!';
                return $data;
            }

            $extra = [];
            if ($device->slot_id && $device->slot_id >= 1 && $device->slot_id <= 6) {
                $extra['slot'] = (int) $device->slot_id;
            }
            if (!$this->mqttService->sendCommand($mqttTopic->name, 'open', $extra)) {
                $data->status = 0;
                $data->message = 'MQTT command failed!';
                return $data;
            }




            $auditHistory = AuditHistory::create([
                'operator_id'   => $authId,
                'device_id'     => $device->id,
                'audit_type'    => 'check_out',
                'audit_lat'     => $lat,
                'audit_long'    => $long,
                'audit_out_time' => now(),
                'audit_date'    => now()->toDateString(),
                'created_by'    => $authId,
                'updated_by'    => $authId,
            ]);

            //add activity log
            $activity = $this->activityLog->create([
                'organization_id' => $device->dock->department->organization_id,
                'department_id'   => $device->dock->department_id,
                'action'          => 'CHECKOUT',
                'entity'          => 'Device',
                'description'     => 'Device checkout',
                'ip_address'      => request()->ip(),
                'created_by'      => $authId,
                'updated_by'      => $authId,
            ]);




            $maxCheckoutHours = BotConfigHelper::getMaxCheckoutHours();
            $lostDeviceDays = BotConfigHelper::getLostDeviceDays();
            $reminderPercentages = BotConfigHelper::getReminderPercentages();

            $checkoutTime   = Carbon::parse($auditHistory->audit_out_time);
            $checkoutTime_Formated   = $auditHistory->audit_out_time_format_time;

            $deadline       = $checkoutTime->copy()->addHours($maxCheckoutHours);
            $checkInTimeLeft = (int) max(0, now()->diffInSeconds($deadline, false));

            $device->device_status = 'inuse';
            $device->return_date = $deadline;
            $device->save();
            DB::commit();

            // Delay Job
            DeviceDelayNotificationJob::dispatch($authId, $device->id, 1)
                ->delay(now()->addHours($maxCheckoutHours));

            // Theft Job
            DeviceTheftNotificationJob::dispatch($authId, $device->id)
                ->delay(now()->addDays($lostDeviceDays));

            // Reminder jobs - percentuais configuráveis (ex: 90%, 99%)
            $totalSeconds = $checkoutTime->diffInSeconds($deadline);
            foreach ($reminderPercentages as $pct) {
                $reminderAt = $checkoutTime->copy()->addSeconds((int)($totalSeconds * ($pct / 100)));
                DeviceReminderNotificationJob::dispatch($authId, $device->id)->delay($reminderAt);
            }

            // Immediate real-time FCM push on checkout
            try {

                (new FCMService(app('log')))->sendToToken(
                    $fcm_token,
                    "Device Checked Out",
                    "Your device ({$device->name}) has been checked out successfully.",
                    [
                        'event' => 'device_checkout',
                        'device_id' => $device->id,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Checkout push failed: Device Id - '.$device->id.', Operator Id - '.$authId.' Error: '.$e->getMessage());
            }

            $data->id = $device->id;
            $data->name = $device->name ?? '';
            $data->device_status = $device->device_status;
            $data->checked_out_time = $checkoutTime_Formated;
            $data->check_in_time_left = $checkInTimeLeft;

            return $data;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device failed to checkout', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Device checkin process
    public function checkin($id,$authId,$lat,$long,$fcm_token)
    {

        $data = new stdClass();

        DB::beginTransaction();
        try {

            $lastAudit = AuditHistory::where('operator_id', $authId)
            ->latest('id')
            ->first();

            if ($lastAudit && $lastAudit->audit_type === 'check_out') {

                $device = Device::findOrFail($id);


                $topicId = $device->dock_mqtt_topic_id;

                if(!$topicId || $topicId == 0) {
                    $data->status = 0;
                    $data->message = 'Active device is not linked to the device’s dock.';

                    return $data;
                }

                $mqttTopic = MqttTopic::find($topicId);
                if (!$mqttTopic || !$mqttTopic->is_active) {
                    Log::warning('MQTT: Tópico não encontrado ou inativo', ['topic_id' => $topicId]);
                    $data->status = 0;
                    $data->message = 'MQTT command failed!';
                    return $data;
                }

                $extra = [];
                if ($device->slot_id && $device->slot_id >= 1 && $device->slot_id <= 6) {
                    $extra['slot'] = (int) $device->slot_id;
                }
                if (!$this->mqttService->sendCommand($mqttTopic->name, 'close', $extra)) {
                    $data->status = 0;
                    $data->message = 'MQTT command failed!';
                    return $data;
                }

                $device->device_status = 'available';
                $device->return_date = NULL;
                $device->save();

                //if notification exists as pending then mark as resolved (on device checkin)
                Notification::where('device_id', $device->id)
                ->where('operator_id', $authId)
                ->where('status', 'pending')
                ->update(['status' => 'resolved']);

                $auditHistory = AuditHistory::create([
                    'operator_id'   => $authId,
                    'device_id'     => $device->id,
                    'audit_type'    => 'check_in',
                    'audit_lat'     => $lat,
                    'audit_long'    => $long,
                    'audit_in_time' => now(),
                    'audit_date'    => now()->toDateString(),
                    'created_by'    => $authId,
                    'updated_by'    => $authId,
                ]);

                //add activity log
                $activity = $this->activityLog->create([
                    'organization_id' => $device->dock->department->organization_id,
                    'department_id'   => $device->dock->department_id,
                    'action'          => 'CHECKIN',
                    'entity'          => 'Device',
                    'description'     => 'Device checkin',
                    'ip_address'      => request()->ip(),
                    'created_by'      => $authId,
                    'updated_by'      => $authId,
                ]);

                DB::commit();

                // Real-time push on check-in (to operator)
                try {
                    $fcm = new FCMService(app('log'));
                    $fcm->sendToToken(
                        $fcm_token,
                        "Device Checked In",
                        "You have successfully checked in device {$device->name}.",
                        ['event'=>'device_checkin','device_id'=>$device->id]
                    );
                } catch (\Throwable $e) {
                    Log::error('Checkin push failed: Device Id - '.$device->id.', Operator Id - '.$authId.' Error: '.$e->getMessage());

                }

                $data->status = 1;
                $data->message = 'Device check in successfully.';
                return $data;

            } else {
                $data->status = 0;
                $data->message = 'Device can`t be checked in.';
                return $data;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device failed to checkin', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Capture device location log
    public function captureDeviceLocation($data)
    {
        DB::beginTransaction();
        try {

            $device_location_log = DeviceLocationLog::create($data);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Device location log creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $device_location_log;

    }

}
