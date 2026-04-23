<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AuditHistory;
use App\Models\Operator;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class DeviceReminderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $operatorId;
    public $deviceId;

    public function __construct($operatorId, $deviceId)
    {
        $this->operatorId = $operatorId;
        $this->deviceId = $deviceId;
    }

    public function handle(FCMService $fcm)
    {
        // Check latest audit for this device
        $lastAudit = AuditHistory::where('device_id', $this->deviceId)->latest('id')->first();

        // If last audit is check_in => nothing to do
        if ($lastAudit && $lastAudit->audit_type === 'check_in') {
            return;
        }

        // Get latest operator token
        $operator = Operator::find($this->operatorId);
        $token = $operator->fcm_token ?? null;

        if (!$token) {
            Log::info("Reminder: no token for operator {$this->operatorId}, skipping.");
            return;
        }

        // Send reminder
        $fcm->sendToToken(
            $token,
            "Check-In Reminder",
            "Your device check-in time is nearing. Please return the device on time.",
            ['event' => 'checkin_reminder', 'device_id' => $this->deviceId]
        );
    }
}
