<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Device;

class DeviceLocationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}


