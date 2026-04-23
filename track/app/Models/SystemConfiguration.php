<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
class SystemConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'theme', 'language', 'time_zone', 'date_format', 'status', 'organization_id', 'user_id', 'operator_id', 'created_by', 'updated_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
