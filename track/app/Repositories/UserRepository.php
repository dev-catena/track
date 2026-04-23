<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Operator;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Support\Facades\Password;
use App\Notifications\SetPasswordNotification;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\ActivityLogInterface;

class UserRepository implements UserInterface
{

    protected $activityLog;

    public function __construct(ActivityLogInterface $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    // Retrieve all users/operators with search and filter
    public function all($search, $filterType, $filter, $user, $organizationId = null)
    {
        // empty collection
        $users = collect();
        $operators = collect();

        if ($filterType == 1 && $filter) {
            if ($filter === 'operator') {
                // Fetch only operators
                $operatorQuery = Operator::select('id', 'organization_id', 'department_id', 'name', 'email', 'phone', 'status', DB::raw("'operator' as role"), 'created_at','qr_token','face_id','avatar');

                // role-based filter
                if ($user->role === 'admin') {
                    $operatorQuery->where('organization_id', $user->organization_id);
                } elseif ($user->role === 'manager') {
                    $operatorQuery->where('department_id', $user->department_id);
                } elseif ($user->role === 'superadmin' && $organizationId) {
                    $operatorQuery->where('organization_id', $organizationId);
                }

                if (!empty($search)) {
                    $operatorQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%");
                    });
                }

                $operators = $operatorQuery->get()->map(function ($item) {
                    $item->type = 'operator';
                    return $item;
                });

            } else {
                // Fetch only users with matching role
                $userQuery = User::select('id', 'organization_id', 'department_id', 'name', 'email', 'phone', 'status', 'role', 'created_at', 'avatar', DB::raw("'qr_token' as qr_token"))->where([['role','!=','superadmin'],['id','!=',$user->id]])->where('role', $filter);

                // role-based filter
                if ($user->role === 'admin') {
                    $userQuery->where('organization_id', $user->organization_id);
                } elseif ($user->role === 'manager') {
                    $userQuery->where('department_id', $user->department_id);
                } elseif ($user->role === 'superadmin' && $organizationId) {
                    $userQuery->where('organization_id', $organizationId);
                }

                if (!empty($search)) {
                    $userQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%");
                    });
                }

                if($user->role == 'admin') {
                    $userQuery->where('organization_id',$user->organization_id);
                }

                $users = $userQuery->get()->map(function ($item) {
                    $item->type = 'user';
                    return $item;
                });
            }

        } else {
            // No filterType = 1 or no filter → fetch both

            $userQuery = User::select('id', 'organization_id', 'department_id', 'name', 'email', 'phone', 'status', 'role', 'created_at', 'avatar', DB::raw("'qr_token' as qr_token"))->where([['role','!=','superadmin'],['id','!=',$user->id]]);

            $operatorQuery = Operator::select('id', 'organization_id', 'department_id', 'name', 'email', 'phone', 'status', DB::raw("'operator' as role"), 'created_at','qr_token','face_id','avatar');

             // role-based filter
            if ($user->role === 'admin') {
                $userQuery->where('organization_id', $user->organization_id);
                $operatorQuery->where('organization_id', $user->organization_id);
            } elseif ($user->role === 'manager') {
                $userQuery->where('department_id', $user->department_id);
                $operatorQuery->where('department_id', $user->department_id);
            } elseif ($user->role === 'superadmin' && $organizationId) {
                $userQuery->where('organization_id', $organizationId);
                $operatorQuery->where('organization_id', $organizationId);
            }

            if (!empty($search)) {
                foreach ([$userQuery, $operatorQuery] as $query) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('status', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%");
                    });
                }
            }
            if($user->role == 'admin') {
                $userQuery->where('organization_id',$user->organization_id);
            }

            $users = $userQuery->get()->map(function ($item) {
                $item->type = 'user';
                return $item;
            });

            $operators = $operatorQuery->get()->map(function ($item) {
                $item->type = 'operator';
                return $item;
            });
        }

        // Merge and sort
        $combined = $users->merge($operators)->sortByDesc('created_at')->values();

        return $combined;
    }


    // Find user by ID
    public function find($id)
    {
        return User::findOrFail($id);
    }

    // Create a new organization admin user
    public function createOrganizationAdmin($organization) {

        $user = $organization->users()->create([
            'name' => $organization->name,
            'email' => $organization->email,
            'role' => 'admin',
            'password' => Hash::make('12345678'),
            'plain_password' => '12345678',
            'created_by' => $organization->created_by,
            'updated_by' => $organization->updated_by,
            'organization_id' => $organization->id,
        ]);

        //run after sending response to user
        dispatch(function () use ($user) {
            Password::sendResetLink($user->only('email'));
        })->afterResponse();

        return $user;
    }

    // Update an organization admin user
    public function updateOrganizationAdmin($organization)
    {

        $admin = $organization->users()->where('role', 'admin')->first();

        if ($admin) {
            $admin->update([
                'name' => $organization->name,
                'email' => $organization->email,
                'phone' => $organization->phone,
                'updated_by' => $organization->updated_by,
            ]);
        }

        return $admin;
    }


    // Create a new user
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $user = User::create($data);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $user->organization_id,
            //     'department_id' => $user->department_id,
            //     'action'          => 'CREATE',
            //     'entity'          => 'User',
            //     'description'     => 'Created User',
            //     'ip_address'      => request()->ip(),
            //     'created_by'      => $user->created_by,
            //     'updated_by'      => $user->updated_by,
            // ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $user;

    }

    // Update an existing user
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $user = User::findOrFail($id);
            $user->update($data);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $user->organization_id,
            //     'department_id' => $user->department_id,
            //     'action'          => 'UPDATE',
            //     'entity'          => 'User',
            //     'description'     => 'Updated User',
            //     'ip_address'      => request()->ip(),
            //     'created_by'      => $data['updated_by'],
            //     'updated_by'      => $data['updated_by'],
            // ]);


            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('user updation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        return $user;
    }

    // Delete a user
    public function delete($id)
    {
        DB::beginTransaction();
        try {

            $user = User::findOrFail($id);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $user->organization_id,
            //     'department_id' => $user->department_id,
            //     'action'          => 'DELETE',
            //     'entity'          => 'User',
            //     'description'     => 'Deleted User',
            //     'ip_address'      => request()->ip(),
            //     'created_by'      => $data['updated_by'],
            //     'updated_by'      => $data['updated_by'],
            // ]);

            $user->delete();
            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Retrieve specific user details
    public function detail($id)
    {
        $user = User::select('id','organization_id','department_id','name','email','phone','status','role','created_at','operation','avatar')
        ->where('id',$id)->first();

        return $user;
    }
}
