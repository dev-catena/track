<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Department;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class BotConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key', 'value', 'type', 'category', 'department_id', 'description','created_by', 'updated_by'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class,'department_id');
    }

    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format("$format H:i:s");
    }
    public function getUpdatedAtAttribute($value)
    {
        return $this->formatDateTime($value);
    }
}
