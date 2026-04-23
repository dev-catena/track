<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Device;
use App\Models\Operator;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class AuditHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'operator_id',
        'device_id',
        'audit_type',
        'audit_lat',
        'audit_long',
        'audit_in_time',
        'audit_out_time',
        'audit_date',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'audit_out_time_format_time',
        'audit_out_time_format_datetime',
        'audit_in_time_format_time',
        'audit_in_time_format_datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function device()
    {
        return $this->belongsTo(Device::class,'device_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    private function formatTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        //$format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format("H:i:s");
    }
    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format("$format H:i:s");
    }
    public function getAuditOutTimeFormatTimeAttribute()
    {
        return $this->formatTime($this->attributes['audit_out_time']);
    }

    public function getAuditInTimeFormatTimeAttribute()
    {
        return $this->formatTime($this->attributes['audit_in_time']);
    }

    public function getAuditOutTimeFormatDatetimeAttribute()
    {
        return $this->formatDateTime($this->attributes['audit_out_time']);
    }

    public function getAuditInTimeFormatDatetimeAttribute()
    {
        return $this->formatDateTime($this->attributes['audit_in_time']);
    }
}
