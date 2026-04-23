<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\OperatorInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use App\Models\Operator;
use Illuminate\Support\Facades\Password;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use App\Services\ThalamusFaceService;

class OperatorController extends Controller
{
    protected $operator;

    public function __construct(OperatorInterface $operator)
    {
        $this->operator = $operator;
    }


    // Retrieve specific operator details
    public function detail(Request $request, $id) {
        $detail = $this->operator->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Data retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing operator
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'organization_id'   => 'required',
                'department_id'     => 'required',
                'operation'         => 'nullable|string|in:indoor,outdoor',
                'phone'             => 'nullable|string',
                'status'            => 'required',
                'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'email'             => 'required|string|email|unique:operators,email,' . $id,
                'username'          => 'required|string|unique:operators,username,' . $id,
            ]);

            $data['operation'] = $data['operation'] ?? 'indoor';

            if ($request->hasFile('avatar')) {
                $operator = $this->operator->find($id);
                if ($operator->avatar) {
                    Storage::disk('public')->delete($operator->avatar);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $data['updated_by'] = Auth::id();
            $this->operator->update($id, $data);

            return $this->sendJsonResponse(1,'Details updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete an operator
    public function destroy($id)
    {
        $this->operator->delete($id);
        return $this->sendJsonResponse(1,'User deleted successfully.');
    }

    // Register an operator's face (Version 1)
    public function faceRegister(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $operator = $this->operator->find($id);

            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png|max:4096',
            ]);



            $file = $request->file('image');
            $imgPath = $file->getPathname();

            try {

                $result = $this->faceDetect($imgPath);

                if (!$result['ok']) {
                    return $this->sendJsonResponse(0, $result['message'] ?? $result['error'] ?? 'Face not detected');
                }

            } catch (\Exception $e) {
                return $this->sendJsonResponse(0, 'Face detection failed: '.$e->getMessage());
            }

            if (!empty($operator->face_id) && !empty($operator->face_extension)) {
                $oldPath = public_path("operator_faces/{$operator->face_id}.{$operator->face_extension}");
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $faceId = uniqid("face_");
            $extension = $file->getClientOriginalExtension();
            $fileName = "{$faceId}.{$extension}";

            $file->move(public_path('operator_faces'), $fileName);

            $this->operator->update($id, [
                'face_id'        => $enroll['uuid'],
                'face_extension' => $extension,
                'updated_by'     => Auth::id(),
            ]);

            DB::commit();

            return $this->sendJsonResponse(1, 'Face registered successfully.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Register an operator's face (Version 2 - Thalamus)
    public function faceRegisterV2(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $operator = $this->operator->find($id);

            $request->validate([
                'image' => 'required|file|mimes:jpg,jpeg,png|max:4096',
            ]);

            $thalamus = new ThalamusFaceService;
            if (! $thalamus->isConfigured()) {
                return $this->sendJsonResponse(0, 'Thalamus Face API not configured (THALAMUS_FACE_BASE_URL).');
            }

            $imagePath = $request->file('image')->path();
            $faceId = ThalamusFaceService::operatorFaceId((int) $operator->id);
            $result = $thalamus->registerFromImage($imagePath, $faceId);

            if (! $result['ok']) {
                return $this->sendJsonResponse(0, $result['message']);
            }

            $this->operator->update($id, [
                'face_id' => $faceId,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return $this->sendJsonResponse(1, 'Face registered successfully.');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }
    // Retrieve face details (Version 1)
    public function faceDetail(Request $request, $id) {


        $detail = $this->operator->facedetail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'Data retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Retrieve face details (Version 2 - face_id no Thalamus / banco local)
    public function faceDetailV2(Request $request, $id) {

        $detail = $this->operator->facedetailV2($id);

        if($detail) {
            if($detail->status == 1) {
                return $this->sendJsonResponse(1,'Data retrieved successfully.',$detail);

            } else if($detail->status == -1) {
                return $this->sendJsonResponse(-1,$detail->message,$detail);
            } else {
                return $this->sendJsonResponse(0,'No data found!');
            }
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    private function faceDetect($imagePath)
    {
        $python = '/home/master/newenv/bin/python';
        //$python = 'C:\Users\Ranjit Protolabz\AppData\Local\Programs\Python\Python39\python.exe';
        $script = base_path('face_detect.py');

        $process = new Process([$python, $script, "--probe", $imagePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            return ['ok' => false, 'error' => 'Python process failed'.$process->getErrorOutput() ?: $process->getOutput()];
        }

        $output = json_decode($process->getOutput(), true);

        if (!$output) {
            return ['ok' => false, 'error' => 'Invalid Python response'];
        }

        return $output;
    }

    // Change operator password
    public function changePassword(Request $request, $id)
    {
        try {
            $data1 = $request->validate([
                'current_password'  => 'nullable',
                'new_password'  => 'required|min:6|confirmed',
            ]);

            $operator = $this->operator->find($id);
            $currentUser = Auth::user();

            $actorRole = strtolower((string) $currentUser->role);
            $canSkipCurrent = in_array($actorRole, ['superadmin', 'admin', 'manager'], true);

            // Painel web: só perfis privilegiados chamam esta rota para redefinir operador — não exige senha do operador.
            if ($canSkipCurrent) {
                // sem validação de current_password
            } elseif (trim((string) $request->current_password) === '') {
                return $this->sendJsonResponse(0, 'Informe a senha atual ou peça a um administrador para redefinir.');
            } elseif (!Hash::check($request->current_password, $operator->password)) {
                return $this->sendJsonResponse(0, 'Senha atual incorreta.');
            }

            $data['password'] = Hash::make($data1['new_password']);
            $data['plain_password'] = $data1['new_password'];
            $data['updated_by'] = Auth::id();
            $this->operator->update($id, $data);

            return $this->sendJsonResponse(1, 'Senha alterada com sucesso.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }
}
