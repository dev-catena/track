<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'name',
        'code',
        'requires_username',
        'is_operator',
        'assignable_by',
        'sort_order',
    ];

    protected $casts = [
        'requires_username' => 'boolean',
        'is_operator' => 'boolean',
    ];

    public function functionalities()
    {
        return $this->belongsToMany(Functionality::class, 'profile_functionality');
    }

    public function hasFunctionality(string $slug): bool
    {
        return $this->functionalities()->where('slug', $slug)->exists();
    }

    public function isAssignableBy(string $role): bool
    {
        if (empty($this->assignable_by)) {
            return true;
        }
        $roles = array_map('trim', explode(',', $this->assignable_by));
        return in_array($role, $roles);
    }
}
