<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Operator;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'department_id',
        'action',
        'entity',
        'description',
        'ip_address',
        'created_by',
        'updated_by',
    ];

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdByOperator()
    {
        return $this->belongsTo(Operator::class, 'created_by');
    }
}
