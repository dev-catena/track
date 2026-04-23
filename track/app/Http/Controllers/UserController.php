<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Repositories\Interfaces\DepartmentInterface;
use App\Repositories\Interfaces\UserInterface;
use App\Repositories\Interfaces\OperatorInterface;
use App\Repositories\Interfaces\ProfileInterface;
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
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SessionController;

class UserController extends Controller
{
    protected $organization;
    protected $department;
    protected $user;
    protected $operator;
    protected $profile;

    public function __construct(OrganizationInterface $organization, DepartmentInterface $department, UserInterface $user, OperatorInterface $operator, ProfileInterface $profile)
    {
        $this->organization = $organization;
        $this->department = $department;
        $this->user = $user;
        $this->operator = $operator;
        $this->profile = $profile;
    }
    // List all users
    public function index(Request $request)
    {

        $user = Auth::user();

        if ($request->ajax()) {

            $search = '';
            $filterType = 0;
            $filter = '';
            $organizationId = null;
            if ($request->filled('role')) {
                $filterType = 1;
                $filter = $request->role;
            }
            if ($request->has('search') && $request->search['value'] !== null) {
                $search = $request->search['value'];
            }
            // Superadmin: filtrar por empresa selecionada (sessão ou request)
            if ($user->role === 'superadmin') {
                $organizationId = $request->filled('organization_id')
                    ? (int) $request->organization_id
                    : session(SessionController::SESSION_KEY);
            }

            $data = $this->user->all($search, $filterType, $filter, $user, $organizationId);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name_column', function ($row) {
                    return view('partials.user_table_name', ['user' => $row])->render();
                })
                ->addColumn('status_column', function ($row) {
                    $cls = $row->status == 'active' ? 'badge-success' : 'badge-danger';
                    return '<span class="badge ' . $cls . ' rounded-4 p-2">' . ucfirst($row->status ?? '') . '</span>';
                })
                ->addColumn('role_column', function ($row) {
                    return '<span class="badge badge-primary rounded-4 p-2">' . ucfirst($row->role ?? '') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('partials.user_table_actions', ['user' => $row])->render();
                })
                ->rawColumns(['name_column', 'status_column', 'role_column', 'action'])
                ->make(true);
        }

        $organizations = [];
        $selectedOrganizationId = null;
        if($user->role == 'superadmin') {
            $organizations = $this->organization->organization_list();
            $sid = session(SessionController::SESSION_KEY);
            $selectedOrganizationId = $sid !== null && $sid !== '' ? (int) $sid : null;
            if (! $selectedOrganizationId && $organizations->isNotEmpty()) {
                $selectedOrganizationId = (int) $organizations->keys()->first();
            }
        } else  {
            $organizations = $user->organization_id;
        }

        if($user->role == 'admin') {
            $departments = $this->department->departmentsByCompanyId($user->organization_id);
        } else {
            $departments = $user->department_id;
        }

        $profiles = $this->profile->listForUserForm($user->role);
        return view('common.user.index', compact('organizations', 'departments', 'profiles', 'selectedOrganizationId'));
    }

    // Store a new user
    public function store(Request $request)
    {
        try {

            if ($request->filled('role')) {
                $request->merge([
                    'role' => $this->resolveCanonicalRole((string) $request->input('role')),
                ]);
            }

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'organization_id'   => 'required',
                'department_id' => [
                    'required_unless:role,admin',
                ],
                'operation'         => 'nullable|string|in:indoor,outdoor',
                'phone'             => 'nullable|string',
                //'username'          => 'nullable|string',
                'role'              => 'required',
                'status'            => 'required',
                'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'email' => [
                    'required',
                    'string',
                    'email',
                    function ($attribute, $value, $fail) {
                        $existsInUsers = User::where('email', $value)->exists();
                        $existsInOperators = Operator::where('email', $value)->exists();

                        if ($existsInUsers || $existsInOperators) {
                            $fail('The email has already been taken.');
                        }
                    },
                ],
            ]);

            $allowedRoles = ['superadmin', 'admin', 'manager', 'supervisor', 'user', 'operator'];
            if (! in_array($data['role'], $allowedRoles, true)) {
                return $this->sendJsonResponse(0, 'Perfil inválido. Use um código de perfil válido (ex.: operator, admin, manager).');
            }

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $plainPassword = !empty($request->password) ? $request->password : '12345678';
            $data['password'] = Hash::make($plainPassword);
            $data['plain_password'] = $plainPassword;
            $data['qr_token'] = Str::uuid();
            if (empty($data['operation'] ?? null)) {
                $data['operation'] = 'indoor';
            }
            $data['updated_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            if ($data['role'] == 'operator') {
                $username = trim((string) $request->input('username', ''));
                if ($username === '') {
                    $username = $this->generateUniqueOperatorUsername((string) $data['email']);
                }
                if (Operator::where('username', $username)->exists()) {
                    return $this->sendJsonResponse(0, 'Nome de usuário já está em uso por outro operador.');
                }
                $data['username'] = $username;
                $user = $this->operator->create($data);
            } else {
                $user = $this->user->create($data);
            }


            return $this->sendJsonResponse(1,'User created successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Retrieve specific user details
    public function detail(Request $request, $id) {
        $detail = $this->user->detail($id);

        if($detail) {
            return $this->sendJsonResponse(1,'User detail retrieved successfully.',$detail);
        } else {
            return $this->sendJsonResponse(0,'No data found!');
        }
    }

    // Update an existing user
    public function update(Request $request, $id)
    {
        try {

            $data = $request->validate([
                'name'              => 'required|string|max:255',
                'organization_id'   => 'required',
                // 'department_id' => [
                //     'required_unless:role,admin',
                // ],
                'operation'         => 'nullable|string|in:indoor,outdoor',
                'phone'             => 'nullable|string',
                //'role'              => 'required',
                'status'            => 'required',
                'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'email'             => 'required|string|email|unique:users,email,' . $id,
            ]);

            if ($request->hasFile('avatar')) {
                $user = $this->user->find($id);
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $data['updated_by'] = Auth::id();
            if (empty($data['operation'] ?? null)) {
                unset($data['operation']);
            }
            $this->user->update($id, $data);

            return $this->sendJsonResponse(1,'Details updated successfully.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0,$firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0,$e->getMessage());
        }
    }

    // Delete a user
    public function destroy($id)
    {
        $this->user->delete($id);
        return $this->sendJsonResponse(1,'User deleted successfully.');
    }


    // Change user password
    public function changePassword(Request $request, $id)
    {
        try {
            $data1 = $request->validate([
                'current_password'  => 'nullable',
                'new_password'  => 'required|min:6|confirmed',
            ]);

            $user = $this->user->find($id);
            $currentUser = Auth::user();

            $actorRole = strtolower((string) $currentUser->role);
            $canSkipCurrent = in_array($actorRole, ['superadmin', 'admin', 'manager'], true);
            $isSelf = (int) $currentUser->id === (int) $user->id;

            // Ao redefinir a senha de outro usuário, admin não precisa (nem deve) informar a senha antiga do alvo.
            // Se preencher o campo por engano (ex.: a própria senha), a checagem antiga falhava indevidamente.
            if (!$isSelf && $canSkipCurrent) {
                // sem validação de current_password
            } elseif (trim((string) $request->current_password) === '') {
                return $this->sendJsonResponse(0, $isSelf
                    ? 'Informe a senha atual.'
                    : 'Informe a senha atual ou peça a um administrador para redefinir.');
            } elseif (!Hash::check($request->current_password, $user->password)) {
                return $this->sendJsonResponse(0, 'Senha atual incorreta.');
            }

            $data['password'] = Hash::make($data1['new_password']);
            $data['plain_password'] = $data1['new_password'];
            $data['updated_by'] = Auth::id();
            $this->user->update($id, $data);

            return $this->sendJsonResponse(1, 'Senha alterada com sucesso.');

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return $this->sendJsonResponse(0, $firstError);

        } catch (Exception $e) {
            return $this->sendJsonResponse(0, $e->getMessage());
        }
    }


    // Display user profile
    public function profile(Request $request) {

        return view('common.profile.index');
    }

    /**
     * Normaliza código de perfil (PT/EN e tabela profiles) para o valor usado no sistema.
     * Ex.: "operador" → "operator" para gravar em operators; ENUM users só aceita admin/manager/...
     */
    private function resolveCanonicalRole(string $incoming): string
    {
        $t = strtolower(trim($incoming));

        $aliases = [
            'operador' => 'operator',
            'operadores' => 'operator',
            'administrador' => 'admin',
            'gerente' => 'manager',
            'usuário' => 'user',
            'usuario' => 'user',
        ];

        if (isset($aliases[$t])) {
            return $aliases[$t];
        }

        $profile = Profile::query()
            ->whereRaw('LOWER(TRIM(code)) = ?', [$t])
            ->orWhereRaw('LOWER(TRIM(name)) = ?', [$t])
            ->first();

        if ($profile && $profile->code !== null && $profile->code !== '') {
            $code = strtolower(trim((string) $profile->code));

            return $aliases[$code] ?? $code;
        }

        return $t;
    }

    /** Username obrigatório em operators: gera a partir do e-mail se o formulário vier vazio. */
    private function generateUniqueOperatorUsername(string $email): string
    {
        $local = strtolower((string) Str::before(trim($email), '@'));
        $base = Str::slug($local, '_');
        if ($base === '') {
            $base = 'operador';
        }
        $base = Str::substr($base, 0, 160);
        $candidate = $base;
        $n = 0;
        while (Operator::where('username', $candidate)->exists()) {
            $n++;
            $suffix = '_' . $n;
            $candidate = Str::substr($base, 0, 190 - strlen($suffix)) . $suffix;
        }

        return $candidate;
    }
}
