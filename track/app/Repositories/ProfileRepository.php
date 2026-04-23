<?php

namespace App\Repositories;

use App\Models\Profile;
use App\Repositories\Interfaces\ProfileInterface;

class ProfileRepository implements ProfileInterface
{
    public function all($search = '')
    {
        $query = Profile::orderBy('sort_order')->orderBy('name');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        return $query->get();
    }

    public function detail($id)
    {
        return Profile::find($id);
    }

    public function create($data)
    {
        return Profile::create($data);
    }

    public function update($id, $data)
    {
        $profile = Profile::findOrFail($id);
        $profile->update($data);
        return $profile;
    }

    public function delete($id)
    {
        $profile = Profile::findOrFail($id);
        $profile->delete();
        return true;
    }

    public function listForUserForm(string $currentUserRole): array
    {
        return Profile::orderBy('sort_order')
            ->get()
            ->filter(fn ($p) => $p->isAssignableBy($currentUserRole))
            ->values()
            ->all();
    }
}
