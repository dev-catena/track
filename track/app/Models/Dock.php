<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Organization;
use App\Models\Department;
use App\Models\Device;
use App\Models\MqttTopic;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Dock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'status',
        'dock_status',
        'capacity',
        'description',
        'dock_number',
        'department_id',
        'created_by',
        'updated_by',
        'mqtt_topic_id',
        'pairing_code',
    ];


    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format($format);
    }
    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateTime($value);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function organization()
    {
        return $this->hasOneThrough(
            Organization::class,   // final model
            Department::class,     // intermediate model
            'id',                  // Department.id
            'id',                  // Organization.id
            'department_id',       // Dock.department_id
            'organization_id'      // Department.organization_id
        );
    }

    public function mqttTopic()
    {
        return $this->belongsTo(MqttTopic::class, 'mqtt_topic_id');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'dock_id');
    }

    public function activeAvailableDevices()
    {
        return $this->hasMany(Device::class)
            ->where('status', 'active')
            ->where('device_status', 'available');
    }

    public function getUsageCapacityAttribute()
    {
        return $this->devices_count . ' / ' . $this->capacity;
    }
}
