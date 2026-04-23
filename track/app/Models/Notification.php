<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Device;
use App\Models\User;
use App\Models\Operator;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_id',
        'operator_id',
        'user_id',
        'status',
        'type',
        'title',
        'description',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
}
