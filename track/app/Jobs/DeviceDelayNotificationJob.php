<?php

namespace App\Jobs;

use App\Models\AuditHistory;
use App\Models\Notification;
use App\Models\Operator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\BotConfigHelper;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class DeviceDelayNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operatorId;
    protected $deviceId;
    protected $attemptNumber;

    public function __construct($operatorId, $deviceId, $attemptNumber = 1)
    {
        $this->operatorId = $operatorId;
        $this->deviceId = $deviceId;
        $this->attemptNumber = $attemptNumber;
    }

    public function handle(FCMService $fcm)
    {

        //Log::info("Delay notification started creation for device ID {$this->deviceId}");

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

                if ($this->attemptNumber == 1) {
                    // create notification in db
                    Notification::create([
                        'device_id'   => $audit->device_id,
                        'operator_id' => $audit->operator_id,
                        'status'      => 'pending',
                        'type'        => 'delay',
                        'title'       => 'Device Overdue',
                        'description' => "Device <b>{$audit->device->name}</b> has not been checked in after allowed time.",
                    ]);
                }

                //send firebase push notification to the app
                $operator = Operator::find($this->operatorId);
                $token = $operator->fcm_token ?? null;

                if (!$token) {
                    Log::info("Delay: no token for operator {$this->operatorId}, skipping.");
                    return;
                }

                $fcm->sendToToken(
                    $token,
                    "Check-In Time Over",
                    "Your device check-in time has expired. Please return the device immediately.",
                    ['event' => 'checkout_timeout', 'device_id' => $this->deviceId]
                );



                // Schedule next retry - intervalo configurável (delay_retry_interval_minutes)
                $intervalMinutes = BotConfigHelper::getDelayRetryIntervalMinutes();
                $nextAttempt = $this->attemptNumber + 1;
                self::dispatch(
                    $this->operatorId,
                    $this->deviceId,
                    $nextAttempt
                )->delay(now()->addMinutes($intervalMinutes));

                //Log::info("Delay notification created for device ID {$this->deviceId}");
            }
        }



    }
}

