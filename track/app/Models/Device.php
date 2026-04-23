<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Dock;
use App\Models\DeviceLocationLog;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'model_name',
        'tag_type',
        'tag_id',
        'dock_id',
        'slot_id',
        'serial_number',
        'status',
        'device_status',
        'description',
        'created_by',
        'updated_by',
        'return_date'
    ];

    public function dock()
    {
        return $this->belongsTo(Dock::class,'dock_id');
    }

    public function getDisplayStatusAttribute()
    {
        if ($this->status === 'active') {
            // device_status badge
            $badgeClass = match (strtolower($this->device_status)) {
                'available' => 'badge-success',
                'inuse'     => 'badge-info',
                'offline'   => 'badge-dark',
                'overdue'   => 'badge-danger',
                default     => 'badge-secondary',
            };

            return '<span class="badge '.$badgeClass.'">'.ucfirst($this->device_status).'</span>';
        } else {
            // status badge
            $badgeClass = match (strtolower($this->status)) {
                'inactive'    => 'badge-warning',
                'maintenance' => 'badge-danger',
                default       => 'badge-secondary',
            };

            return '<span class="badge '.$badgeClass.'">'.ucfirst($this->status).'</span>';
        }
    }


    public function locationLogs()
    {
        return $this->hasMany(DeviceLocationLog::class);
    }

    public function getDockMqttTopicIdAttribute()
    {
        return optional($this->dock)->mqtt_topic_id;
    }

    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format("$format H:i:s");
    }
    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateTime($value);
    }

    public function getReturnDateAttribute($value)
    {
        // if value then return this else reutn -
        if($value) {
            return $this->formatDateTime($value);
        } else {
            return '-';
        }
    }
}
