<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Operator;
use App\Models\Department;
use App\Models\Dock;
use App\Models\Device;
use App\Models\Plan;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'status',
        'created_by',
        'updated_by',
        'cnpj',
        'city',
        'state',
        'plan_id',
        'max_devices',
        'mdm',
        'created_at',
        'updated_at',
        'mqtt_company_id'
    ];


    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format($format);
    }
    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateTime($value);
    }

    public function users() {
        return $this->hasMany(User::class);
    }

    public function operators() {
        return $this->hasMany(Operator::class);
    }

    public function departments() {
        return $this->hasMany(Department::class);
    }
    public function configuration() {
        return $this->hasOne(SystemConfiguration::class);
    }

    public function plan() {
        return $this->belongsTo(Plan::class,'plan_id');
    }

    public function docks()
    {
        return $this->hasManyThrough(Dock::class, Department::class);
    }

    //delete organization related entries
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($organization) {
            if ($organization->isForceDeleting()) {
                $organization->users()->forceDelete();
                $organization->operators()->forceDelete();
                $organization->configuration()?->forceDelete();
            } else {
                $organization->users()->delete();
                $organization->operators()->delete();
                $organization->configuration()?->delete();
            }
        });
    }
}
