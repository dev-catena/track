<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Organization;
use App\Models\SystemConfiguration;
use App\Notifications\SetPasswordNotification;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'plain_password',
        'role',
        'phone',
        'address',
        'avatar',
        'shift_type',
        'organization_id',
        'department_id',
        'operation',
        'status',
        'created_by',
        'updated_by',
        'face_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function systemConfiguration()
    {
        return $this->hasOne(SystemConfiguration::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new SetPasswordNotification($token));
    }

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
}
