<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Organization;
use App\Models\Department;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class Operator extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    protected $guard = 'operators';
    protected $appends = ['face_image', 'avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'qr_token',
        'qr_image',
        'password',
        'plain_password',
        'status',
        'organization_id',
        'department_id',
        'operation',
        'avatar',
        'created_by',
        'updated_by',
        'face_id',
        'face_extension',
        'fcm_token'
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

    public function department() {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function getFaceImageAttribute()
    {
        if (!empty($this->face_id) && !empty($this->face_extension)) {
            return asset("operator_faces/{$this->face_id}.{$this->face_extension}");
        }
        return 'face_image';
    }
}
