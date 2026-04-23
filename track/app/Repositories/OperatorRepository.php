<?php

namespace App\Repositories;

use App\Models\Operator;
use App\Repositories\Interfaces\OperatorInterface;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Interfaces\ActivityLogInterface;

class OperatorRepository implements OperatorInterface
{

    protected $activityLog;

    public function __construct(ActivityLogInterface $activityLog)
    {
        $this->activityLog = $activityLog;
    }


    // Find operator by ID
    public function find($id)
    {
        return Operator::findOrFail($id);
    }



    // Create a new operator
    public function create($data)
    {
        DB::beginTransaction();
        try {

            $operator = Operator::create($data);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $operator->organization_id,
            //     'department_id' => $operator->department_id,
            //     'action'          => 'CREATE',
            //     'entity'          => 'User',
            //     'description'     => 'Created User',
            //     'ip_address'      => request()->ip(),
            //     'created_by'      => $data['created_by'],
            //     'updated_by'      => $data['updated_by'],
            // ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $operator;

    }

    // Update an existing operator
    public function update($id, $data)
    {
        DB::beginTransaction();
        try {

            $operator = Operator::findOrFail($id);
            $operator->update($data);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $operator->organization_id,
            //     'department_id' => $operator->department_id,
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
        return $operator;
    }

    // Delete an operator
    public function delete($id)
    {
        DB::beginTransaction();
        try {

            $operator = Operator::findOrFail($id);

            //add activity log
            // $activity = $this->activityLog->create([
            //     'organization_id' => $operator->organization_id,
            //     'department_id' => $operator->department_id,
            //     'action'          => 'CREATE',
            //     'entity'          => 'User',
            //     'description'     => 'Deleted User',
            //     'ip_address'      => request()->ip(),
            //     'created_by'      => $data['updated_by'],
            //     'updated_by'      => $data['updated_by'],
            // ]);

            $operator->delete();
            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User failed to delete', ['error' => $e->getMessage()]);
            throw $e;
        }

    }

    // Retrieve specific operator details
    public function detail($id)
    {
        $operator = Operator::select('id','organization_id','department_id','name','email','phone','status',DB::raw("'operator' as role"),'qr_token','created_at','username','operation','avatar')
        ->where('id',$id)->first();

        return $operator;
    }

    // Retrieve face image details (Version 1)
    public function facedetail($id) {

        $face_image = Operator::select('id','face_id','face_extension')
        ->where('id',$id)->first();

        return $face_image;
    }

    // Detalhe facial v2: face_id salvo no operador (Thalamus); sem URL de preview da API
    public function facedetailV2($id)
    {
        $op = Operator::select('id', 'face_id', 'face_extension')
            ->where('id', $id)
            ->first();

        $data = new \stdClass;

        if (! $op || empty($op->face_id)) {
            $data->status = 0;

            return $data;
        }

        $data->status = 1;
        $data->face_id = $op->face_id;
        $data->face_image = null;

        return $data;
    }
}
