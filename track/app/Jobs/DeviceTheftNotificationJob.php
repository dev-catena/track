<?php

namespace App\Jobs;

use App\Models\AuditHistory;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Operator;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class DeviceTheftNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operatorId;
    protected $deviceId;

    public function __construct($operatorId, $deviceId)
    {
        $this->operatorId = $operatorId;
        $this->deviceId = $deviceId;
    }

    public function handle(FCMService $fcm)
    {
        $audit = AuditHistory::where('device_id', $this->deviceId)
        ->where('operator_id', $this->operatorId)
        ->where('audit_type', 'check_out')
        ->whereNull('audit_in_time')
        ->latest()
        ->first();

        if($audit) {
            $isCheckedIn = AuditHistory::where('device_id', $this->deviceId)
            ->where('operator_id', $this->operatorId)
            ->where('audit_type', 'check_in')
            ->whereNotNull('audit_in_time')
            ->where('audit_in_time', '>', $audit->audit_out_time)
            ->exists();

            if (!$isCheckedIn) {
                //send firebase push notification to the app
                $operator = Operator::find($this->operatorId);
                $token = $operator->fcm_token ?? null;

                if (!$token) {
                    Log::info("Theft: no token for operator {$this->operatorId}, skipping.");
                    return;
                }

                $fcm->sendToToken(
                    $token,
                    "Possible Theft Alert",
                    "Your device has not been returned within the configured days. Please contact admin immediately.",
                    ['event' => 'theft_alert', 'device_id' => $this->deviceId]
                );

                //create notification in table
                Notification::create([
                    'device_id'   => $audit->device_id,
                    'operator_id' => $audit->operator_id,
                    'status'      => 'pending',
                    'type'        => 'theft',
                    'title'       => 'Device Theft Alert',
                    'description' => "Device <b>{$audit->device->name}</b> is marked as lost — not returned after allowed days.",
                ]);

                //Log::info("Theft notification created for device ID {$this->deviceId}");
            }
        }



    }
}

