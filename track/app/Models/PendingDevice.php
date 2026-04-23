<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PendingDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'mac_address',
        'device_name',
        'ip_address',
        'wifi_ssid',
        'status',
        'registered_at',
        'device_info',
        'firmware_version',
        'firmware_updated_at',
        'last_seen_at',
        'activated_at',
        'activated_by',
        'mqtt_topic_id',
    ];

    protected $casts = [
        'device_info' => 'array',
        'activated_at' => 'datetime',
        'firmware_updated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function mqttTopic()
    {
        return $this->belongsTo(MqttTopic::class, 'mqtt_topic_id');
    }
}
