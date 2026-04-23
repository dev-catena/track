<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaActivity extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by',
        'firmware_filename',
        'firmware_version',
        'ota_id',
        'sent',
        'failed',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
