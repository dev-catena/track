<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\Device;
use App\Models\AuditHistory;
use App\Models\Organization;
use App\Services\ThalamusFaceService;
use Carbon\Carbon;

class AuthApiController extends Controller
{
    // public function login(Request $request)
    // {

    //     try {
    //         $request->validate([
    //             'type' => 'required|in:email,username,qr_login,face_login',

    //             'email'    => 'required_if:type,email|email',
    //             'username' => 'required_if:type,username',
    //             'password' => 'required_if:type,email,username',

    //             'token'    => 'required_if:type,qr_login',
    //             'image' => 'required_if:type,face_login|file|mimes:jpg,jpeg,png|max:4096',
    //         ]);

    //         if($request->type == 'email' || $request->type == 'username') {
    //             $value = $request->{$request->type};
    //             $operator = Operator::where($request->type, $value)
    //             ->first();
    //             if (! $operator || ! Hash::check($request->password, $operator->password)) {

    //                 return $this->sendJsonResponse(0,'Invalid credentials!');
    //             }

    //         } else if($request->type == 'qr_login') {
    //             $operator = Operator::where('qr_token', $request->token)
    //             ->first();
    //             if(!$operator) {

    //                 return $this->sendJsonResponse(0,'User not found!');
    //             }

    //         }
    //         else if($request->type == 'face_login') {


    //             $result =  $this->faceAuth($request->image);


    //             if (!$result['ok']) {
    //                 return $this->sendJsonResponse(0, $result['error']);
    //             }

    //             if (!isset($result['face_id']) || !$result['face_id']) {
    //                 return $this->sendJsonResponse(0, 'No match found!');
    //             }

    //             $operator = Operator::where('face_id', $result['face_id'])
    //             ->first();

    //             if(!$operator) {
    //                 return $this->sendJsonResponse(0,'Invalid face login!',);
    //             }

    //         }

    //         else {
    //             return $this->sendJsonResponse(0,'Invalid login type!');
    //         }

    //         if($operator->status != 'active' ) {
    //             return $this->sendJsonResponse(0,'User is not active!');

    //         }
    //         $token = $operator->createToken('accessToken')->plainTextToken;

    //         $data = [
    //             'token' => $token,
    //             'operator' => array_merge(
    //                 $operator->only(['id', 'name', 'email','username','phone','organization_id','department_id']),
    //                 [
    //                     'last_picked_device_name' => 'Device-X1',
    //                     'last_picked_device_datetime' => '30/06/2025 09:21:17',
    //                 ]
    //             ),
    //         ];

    //         return $this->sendJsonResponse(1, 'Login success!', $data);


    //     } catch (ValidationException $e) {
    //         $firstError = collect($e->errors())->flatten()->first();
    //         return $this->sendJsonResponse(0,$firstError);

    //     } catch (Exception $e) {
    //         return $this->sendJsonResponse(0,$e->getMessage());
    //     }
    // }

    // Login V2 for operators (Supports multiple login types)
    public function loginV2(Request $request)
    {

        try {
            $request->validate([
                'type' => 'required|in:email,username,qr_login,face_login',

                'email'    => 'required_if:type,email|email',
                'username' => 'required_if:type,username',
                'password' => 'required_if:type,email,username',

                'token'    => 'required_if:type,qr_login',
                'image' => 'required_if:type,face_login|file|mimes:jpg,jpeg,png|max:4096',
                'fcm_token'    => 'nullable|string',
            ]);

            if($request->type == 'email' || $request->type == 'username') {
                $value = $request->{$request->type};
                $operator = Operator::where($request->type, $value)
                ->first();
                if (! $operator || ! Hash::check($request->password, $operator->password)) {

                    return $this->sendJsonResponse(0,'Invalid credentials!');
                }

            } else if($request->type == 'qr_login') {
                $operator = Operator::where('qr_token', $request->token)
                ->first();
                if(!$operator) {

                    return $this->sendJsonResponse(0,'User not found!');
                }

            }

            else if($request->type == 'face_login') {

                $thalamus = new ThalamusFaceService;
                if (! $thalamus->isConfigured()) {
                    return $this->sendJsonResponse(0, 'Reconhecimento facial não configurado (THALAMUS_FACE_BASE_URL).');
                }

                $imagePath = $request->file('image')->getRealPath() ?: $request->file('image')->path();
                $rec = $thalamus->recognizeFromImage($imagePath);

                $traceFace = (bool) config('services.thalamus_face.trace_login') || config('app.debug');
                if ($traceFace) {
                    $raw = $rec['body'] ?? null;
                    $preview = is_array($raw)
                        ? json_encode($raw, JSON_UNESCAPED_UNICODE)
                        : (string) $raw;
                    Log::info('face_login.thalamus', [
                        'ok' => $rec['ok'] ?? null,
                        'recognized_face_id' => $rec['face_id'] ?? null,
                        'message' => $rec['message'] ?? null,
                        'body_preview' => mb_substr($preview, 0, 2500),
                    ]);
                }

                if (! $rec['ok'] || $rec['face_id'] === null || $rec['face_id'] === '') {
                    return $this->sendJsonResponse(0, $rec['message'] ?? 'No match found!');
                }

                $recognizedId = trim((string) $rec['face_id']);

                $operator = Operator::where('face_id', $recognizedId)->first()
                    ?? Operator::whereRaw('TRIM(face_id) = ?', [$recognizedId])->first();

                if ($traceFace) {
                    Log::info('face_login.operator_lookup', [
                        'recognized_id' => $recognizedId,
                        'operator_found' => (bool) $operator,
                    ]);
                }

                if (! $operator) {
                    $linkedUser = User::where('face_id', $recognizedId)
                        ->orWhereRaw('TRIM(face_id) = ?', [$recognizedId])
                        ->first();
                    if ($linkedUser) {
                        return $this->sendJsonResponse(0, 'Este rosto está cadastrado como usuário do painel (admin/gerente), não como operador. No app, use o perfil Operador e registre o rosto nesse cadastro — o botão Iniciar só autentica operadores.');
                    }

                    return $this->sendJsonResponse(0, 'Rosto reconhecido, mas não há operador vinculado. Peça ao administrador para registrar o rosto em um cadastro com perfil Operador.');
                }

            }
            else {
                return $this->sendJsonResponse(0,'Invalid login type!');
            }

            if($operator->status != 'active' ) {
                return $this->sendJsonResponse(0,'User is not active!');

            }

            //save fcm token (only when provided)
            if (!empty($request->fcm_token)) {
                Operator::where('id', $operator->id)->update(['fcm_token' => $request->fcm_token]);
            }

            //create auth token
            $token = $operator->createToken('accessToken')->plainTextToken;

            //get last checked out device details
            $lastCheckout = AuditHistory::where([['operator_id', $operator->id],['audit_type','check_out']])
            ->latest('id')
            ->first();

            $operatorOrgName = $operator->organization_id
                ? (string) Organization::where('id', $operator->organization_id)->value('name')
                : '';

            $data = [
                'token' => $token,
                'operator' => array_merge(
                    $operator->only(['id', 'name', 'email','username','phone','organization_id','department_id']),
                    [
                        'organization_name' => $operatorOrgName,
                        'last_picked_device_name'       => $lastCheckout ? optional($lastCheckout->device)->name : '',
                        'last_picked_device_datetime'   => $lastCheckout ? Carbon::parse($lastCheckout->audit_out_time)->format('d/m/Y H:i:s') : '',
                    ]
                ),
            ];

            return $this->sendJsonResponse(1, 'Login success!', $data);


        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }



    /**
     * Login para admin (User) - usado no tablet para configurar doca e gravar rostos.
     * POST /api/auth/admin/login
     * Body: username ou email + password (form-data)
     */
    public function adminLogin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'username' => 'required_without:email|string',
                'email' => 'required_without:username|email',
                'password' => 'required|string',
            ]);

            $field = $request->filled('email') ? 'email' : 'username';
            $value = $request->input($field);

            $user = User::where($field, $value)->first();

            // Fallback 1: username com @ enviado como username -> busca por email
            if (!$user && $field === 'username' && str_contains($value, '@')) {
                $user = User::where('email', $value)->first();
            }

            // Fallback 2: valor sem @ (ex: pmmg.mg.gov.br) -> tenta email terminando em @valor
            if (!$user && $field === 'username' && !str_contains($value, '@') && str_contains($value, '.')) {
                $candidates = User::where('email', 'LIKE', '%@' . $value)->get();
                if ($candidates->count() === 1) {
                    $user = $candidates->first();
                }
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->sendJsonResponse(0, 'Credenciais inválidas.');
            }

            if (!in_array($user->role, ['superadmin', 'admin', 'manager'])) {
                return $this->sendJsonResponse(0, 'Acesso restrito a administradores.');
            }

            if ($user->status !== 'active') {
                return $this->sendJsonResponse(0, 'Usuário inativo.');
            }

            $token = $user->createToken('admin-tablet')->plainTextToken;

            $tabletOrgName = $user->role === 'superadmin'
                ? 'Roboflex'
                : ($user->organization_id
                    ? (string) Organization::where('id', $user->organization_id)->value('name')
                    : '');

            $data = [
                'token' => $token,
                'user' => array_merge(
                    $user->only(['id', 'name', 'email', 'username', 'role', 'organization_id', 'department_id']),
                    ['organization_name' => $tabletOrgName]
                ),
            ];

            return $this->sendJsonResponse(1, 'Login realizado.', $data);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);
        } catch (Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }

    /**
     * Mock para tablet: retorna token do primeiro operador ativo (sem credenciais).
     * Usado quando o reconhecimento facial está mockado - apenas para testes.
     * POST /api/auth/tablet-mock
     */
    public function tabletMock(Request $request): JsonResponse
    {
        $operatorId = env('TEST_OPERATOR_ID');
        $operator = $operatorId
            ? Operator::where('id', $operatorId)->where('status', 'active')->first()
            : Operator::where('status', 'active')->orderBy('id')->first();

        if (!$operator) {
            return $this->sendJsonResponse(0, 'Nenhum operador ativo cadastrado no sistema.');
        }

        $token = $operator->createToken('accessToken')->plainTextToken;
        $lastCheckout = AuditHistory::where([['operator_id', $operator->id], ['audit_type', 'check_out']])
            ->latest('id')
            ->first();

        $mockOrgName = $operator->organization_id
            ? (string) Organization::where('id', $operator->organization_id)->value('name')
            : '';

        $data = [
            'token' => $token,
            'operator' => array_merge(
                $operator->only(['id', 'name', 'email', 'username', 'phone', 'organization_id', 'department_id']),
                [
                    'organization_name' => $mockOrgName,
                    'last_picked_device_name' => $lastCheckout ? optional($lastCheckout->device)->name : '',
                    'last_picked_device_datetime' => $lastCheckout ? Carbon::parse($lastCheckout->audit_out_time)->format('d/m/Y H:i:s') : '',
                ]
            ),
        ];

        return $this->sendJsonResponse(1, 'Login success!', $data);
    }

    // Logout the user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    // // Authenticate face image using Python script
    // private function faceAuth($image)
    // {
    //     $knownDir = public_path('operator_faces');
    //     $uploadDir = public_path('uploads');
    //     $python  = '/home/master/newenv/bin/python';
    //     //$python = 'C:\Users\Ranjit Protolabz\AppData\Local\Programs\Python\Python39\python.exe';
    //     $script = base_path('compare_deepface.py');

    //     // Save uploaded image
    //     $ext = $image->getClientOriginalExtension();
    //     $tmpName = 'probe_' . bin2hex(random_bytes(8)) . '.' . $ext;
    //     $probePath = $image->move($uploadDir, $tmpName)->getPathname();
    //     // Params
    //     $tolerance = 0.50;
    //     $topK      = max(1, (int) 8);
    //     try {
    //         // Build command
    //         $cmd = [
    //             $python,
    //             $script,
    //             '--probe', $probePath,
    //             '--known_dir', $knownDir,
    //             '--tolerance', (string)$tolerance,
    //             '--top_k', (string)$topK,
    //         ];

    //         $process = new Process($cmd);


    //         // Force environment variables to suppress logs
    //         $process->setEnv([
    //             'PYTHONUTF8'           => '1',
    //             'TF_ENABLE_ONEDNN_OPTS'=> '0',
    //             'TF_CPP_MIN_LOG_LEVEL' => '3',
    //             'PYTHONHASHSEED'       => '0',
    //             'PYTHONMALLOC'         => 'malloc',
    //             'USE_FAST_RANDOM'      => '0',
    //             'DEEPFACE_HOME'        => '/tmp/deepface',
    //         ]);

    //         $process->setTimeout(100);

    //         $process->run();

    //         if (!$process->isSuccessful()) {
    //             return ['ok' => false, 'error' => $process->getErrorOutput() ?: $process->getOutput()];
    //         }
    //         $outputRaw = trim($process->getOutput());
    //         //\Log::info("Python raw output: " . $outputRaw);

    //         if (preg_match('/\{.*\}$/s', $outputRaw, $matches)) {
    //             $output = json_decode($matches[0], true);
    //         } else {
    //             $output = null;
    //         }

    //         if (json_last_error() !== JSON_ERROR_NONE) {
    //             return [
    //                 'ok' => false,
    //                 'error' => 'JSON decode failed: ' . json_last_error_msg(),
    //                 'raw' => $outputRaw
    //             ];
    //         }

    //         if (empty($output['match'])) {
    //             return ['ok' => true, 'face_id' => null, 'output' => $output];
    //         }

    //         return [
    //             'ok' => true,
    //             'face_id' => $output['match']['face_id'],
    //             'output' => $output
    //         ];

    //     } catch (ProcessFailedException $e) {
    //         file_put_contents($debugFile,
    //             "CMD: " . $process->getCommandLine() . PHP_EOL .
    //             "ENV: " . print_r($process->getEnv(), true) . PHP_EOL .
    //             "ERR: " . $process->getErrorOutput()
    //         );
    //         return ['ok' => false, 'error' => $e->getMessage()];

    //     } catch (\Exception $e) {
    //         return ['ok' => false, 'error' => $e->getMessage()];
    //     } finally {
    //         // Delete temporary uploaded image
    //         if (file_exists($probePath)) {
    //             unlink($probePath);
    //         }
    //     }
    // }

}
