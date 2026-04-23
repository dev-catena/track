<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Organization;
use App\Models\Dock;
use App\Models\Device;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'internal_id',
        'location',
        'operating_start',
        'operating_end',
        'description',
        'status',
        'organization_id',
        'parent_id',
        'created_by',
        'updated_by',
        'mqtt_department_id',
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

    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function docks()
    {
        return $this->hasMany(Dock::class);
    }

    public function devices()
    {
        return $this->hasManyThrough(Device::class, Dock::class);
    }
}
