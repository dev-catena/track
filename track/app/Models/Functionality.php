<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Functionality extends Model
{
    protected $fillable = ['name', 'slug', 'platform', 'sort_order'];

    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'profile_functionality');
    }
}
