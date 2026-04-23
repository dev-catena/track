<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SessionController;
use App\Models\AuditHistory;
use App\Models\ActivityLog;
use App\Models\Dock;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function dockHistory(Request $request)
    {
        $user = Auth::user();
        $organizations = collect();
        $selectedOrganizationId = null;
        $effectiveOrgId = null;

        if ($user->role === 'superadmin') {
            $organizations = \App\Models\Organization::orderBy('name')->pluck('name', 'id');
            $selectedOrganizationId = session(SessionController::SESSION_KEY);
            $effectiveOrgId = $selectedOrganizationId ? (int) $selectedOrganizationId : ($organizations->isNotEmpty() ? (int) $organizations->keys()->first() : null);
        } elseif ($user->role === 'admin') {
            $effectiveOrgId = (int) $user->organization_id;
        }

        $docks = $user->role === 'superadmin'
            ? $this->docksForUser($user, $effectiveOrgId)->orderBy('name')->get(['id', 'name', 'department_id'])
            : $this->docksForUser($user)->orderBy('name')->get(['id', 'name', 'department_id']);

        return view('common.reports.dock-history', compact('docks', 'user', 'organizations', 'selectedOrganizationId'));
    }

    public function dockHistoryData(Request $request)
    {
        $request->validate([
            'dock_id' => 'required|integer|exists:docks,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $user = Auth::user();
        $dock = Dock::with('department')->findOrFail($request->dock_id);
        $this->ensureDockAccess($user, $dock);

        $query = AuditHistory::query()
            ->with(['device:id,name,dock_id', 'operator:id,name,email'])
            ->whereHas('device', fn ($q) => $q->where('dock_id', $dock->id));

        if ($request->filled('date_from')) {
            $query->whereDate('audit_histories.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('audit_histories.created_at', '<=', $request->date_to);
        }

        $query->orderByDesc('audit_histories.id');

        return DataTables::of($query)
            ->addColumn('tipo', function ($row) {
                return $row->audit_type === 'check_out' ? 'Checkout' : 'Check-in';
            })
            ->addColumn('dispositivo', fn ($row) => $row->device?->name ?? '—')
            ->addColumn('operador', fn ($row) => $row->operator?->name ?? '—')
            ->addColumn('email_operador', fn ($row) => $row->operator?->email ?? '—')
            ->addColumn('quando', function ($row) {
                if ($row->audit_type === 'check_out') {
                    return $row->audit_out_time_format_datetime ?? $row->audit_out_time ?? '—';
                }

                return $row->audit_in_time_format_datetime ?? $row->audit_in_time ?? '—';
            })
            ->rawColumns([])
            ->make(true);
    }

    public function userOperations(Request $request)
    {
        $user = Auth::user();
        $organizations = collect();
        $selectedOrganizationId = null;
        $effectiveOrgId = null;

        if ($user->role === 'superadmin') {
            $organizations = \App\Models\Organization::orderBy('name')->pluck('name', 'id');
            $selectedOrganizationId = session(SessionController::SESSION_KEY);
            $effectiveOrgId = $selectedOrganizationId ? (int) $selectedOrganizationId : ($organizations->isNotEmpty() ? (int) $organizations->keys()->first() : null);
        }

        $operators = $user->role === 'superadmin'
            ? $this->operatorsForUser($user, $effectiveOrgId)->orderBy('name')->get(['id', 'name', 'email'])
            : $this->operatorsForUser($user)->orderBy('name')->get(['id', 'name', 'email']);

        $users = $user->role === 'superadmin'
            ? $this->webUsersForUser($user, $effectiveOrgId)->orderBy('name')->get(['id', 'name', 'email', 'role'])
            : $this->webUsersForUser($user)->orderBy('name')->get(['id', 'name', 'email', 'role']);

        return view('common.reports.user-operations', compact('operators', 'users', 'user', 'organizations', 'selectedOrganizationId'));
    }

    public function userOperationsData(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:user,operator',
            'subject_id' => 'required|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $user = Auth::user();
        $mode = $request->input('mode');
        $subjectId = (int) $request->input('subject_id');

        if ($mode === 'operator') {
            $operator = Operator::findOrFail($subjectId);
            $this->ensureOperatorAccess($user, $operator);

            $query = AuditHistory::query()
                ->with(['device:id,name,dock_id', 'device.dock:id,name'])
                ->where('operator_id', $operator->id);

            if ($request->filled('date_from')) {
                $query->whereDate('audit_histories.created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('audit_histories.created_at', '<=', $request->date_to);
            }
            $query->orderByDesc('audit_histories.id');

            return DataTables::of($query)
                ->addColumn('origem', fn () => 'Tablet')
                ->addColumn('tipo', function ($row) {
                    return $row->audit_type === 'check_out' ? 'Checkout' : 'Check-in';
                })
                ->addColumn('dispositivo', fn ($row) => $row->device?->name ?? '—')
                ->addColumn('doca', fn ($row) => $row->device?->dock?->name ?? '—')
                ->addColumn('quando', function ($row) {
                    if ($row->audit_type === 'check_out') {
                        return $row->audit_out_time_format_datetime ?? $row->audit_out_time ?? '—';
                    }

                    return $row->audit_in_time_format_datetime ?? $row->audit_in_time ?? '—';
                })
                ->rawColumns([])
                ->make(true);
        }

        $webUser = User::findOrFail($subjectId);
        $this->ensureWebUserAccess($user, $webUser);

        $query = ActivityLog::query()
            ->select([
                'activity_logs.id',
                'activity_logs.action',
                'activity_logs.entity',
                'activity_logs.description',
                'activity_logs.ip_address',
                'activity_logs.created_at',
                'activity_logs.created_by',
            ])
            ->where('created_by', $webUser->id);

        if ($request->filled('date_from')) {
            $query->whereDate('activity_logs.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('activity_logs.created_at', '<=', $request->date_to);
        }
        $query->orderByDesc('activity_logs.id');

        return DataTables::of($query)
            ->addColumn('origem', fn () => 'Painel (atividade)')
            ->addColumn('tipo', fn ($row) => $row->action ?? '—')
            ->addColumn('entidade', fn ($row) => $row->entity ?? '—')
            ->addColumn('detalhe', fn ($row) => $row->description ?? '')
            ->addColumn('ip', fn ($row) => $row->ip_address ?? '—')
            ->addColumn('quando', fn ($row) => $row->created_at ?? '—')
            ->rawColumns([])
            ->make(true);
    }

    public function userOperationsExport(Request $request): StreamedResponse
    {
        $request->validate([
            'mode' => 'required|in:user,operator',
            'subject_id' => 'required|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $user = Auth::user();
        $mode = $request->input('mode');
        $subjectId = (int) $request->input('subject_id');

        $filename = 'operacoes_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($request, $user, $mode, $subjectId) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($mode === 'operator') {
                $operator = Operator::findOrFail($subjectId);
                $this->ensureOperatorAccess($user, $operator);
                fputcsv($out, ['Tipo', 'Operação', 'Dispositivo', 'Data/hora', 'Lat', 'Long'], ';');

                $q = AuditHistory::query()->with('device:id,name')->where('operator_id', $operator->id);
                if ($request->filled('date_from')) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                }
                foreach ($q->orderByDesc('id')->cursor() as $row) {
                    $when = $row->audit_type === 'check_out'
                        ? ($row->audit_out_time ?? '')
                        : ($row->audit_in_time ?? '');
                    fputcsv($out, [
                        $row->audit_type === 'check_out' ? 'Checkout' : 'Check-in',
                        'Tablet',
                        $row->device?->name ?? '',
                        $when,
                        $row->audit_lat ?? '',
                        $row->audit_long ?? '',
                    ], ';');
                }
            } else {
                $webUser = User::findOrFail($subjectId);
                $this->ensureWebUserAccess($user, $webUser);
                fputcsv($out, ['Ação', 'Entidade', 'Descrição', 'IP', 'Data'], ';');

                $q = ActivityLog::query()->where('created_by', $webUser->id);
                if ($request->filled('date_from')) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                }
                foreach ($q->orderByDesc('id')->cursor() as $row) {
                    fputcsv($out, [
                        $row->action ?? '',
                        $row->entity ?? '',
                        $row->description ?? '',
                        $row->ip_address ?? '',
                        $row->getRawOriginal('created_at') ?? '',
                    ], ';');
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function dockHistoryExport(Request $request): StreamedResponse
    {
        $request->validate([
            'dock_id' => 'required|integer|exists:docks,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $user = Auth::user();
        $dock = Dock::findOrFail($request->dock_id);
        $this->ensureDockAccess($user, $dock);

        $filename = 'doca_'.$dock->id.'_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($request, $dock) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Tipo', 'Operador', 'E-mail', 'Dispositivo', 'Data/hora', 'Lat', 'Long'], ';');

            $q = AuditHistory::query()
                ->with(['device:id,name', 'operator:id,name,email'])
                ->whereHas('device', fn ($qq) => $qq->where('dock_id', $dock->id));

            if ($request->filled('date_from')) {
                $q->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate('created_at', '<=', $request->date_to);
            }

            foreach ($q->orderByDesc('id')->cursor() as $row) {
                $when = $row->audit_type === 'check_out'
                    ? ($row->audit_out_time ?? '')
                    : ($row->audit_in_time ?? '');
                fputcsv($out, [
                    $row->audit_type === 'check_out' ? 'Checkout' : 'Check-in',
                    $row->operator?->name ?? '',
                    $row->operator?->email ?? '',
                    $row->device?->name ?? '',
                    $when,
                    $row->audit_lat ?? '',
                    $row->audit_long ?? '',
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function docksForUser($user, ?int $organizationId = null)
    {
        $q = Dock::query();

        if ($user->role === 'superadmin') {
            $orgId = $organizationId ?? session(SessionController::SESSION_KEY);
            if ($orgId) {
                $q->whereHas('department', fn ($d) => $d->where('organization_id', $orgId));
            } else {
                $q->whereRaw('1 = 0');
            }
        } elseif ($user->role === 'admin') {
            $q->whereHas('department', fn ($d) => $d->where('organization_id', $user->organization_id));
        } elseif ($user->role === 'manager') {
            $q->where('department_id', $user->department_id);
        } else {
            $q->whereRaw('1 = 0');
        }

        return $q;
    }

    private function operatorsForUser($user, ?int $organizationId = null)
    {
        $q = Operator::query();

        if ($user->role === 'superadmin') {
            $orgId = $organizationId ?? session(SessionController::SESSION_KEY);
            if ($orgId) {
                $q->where('organization_id', $orgId);
            } else {
                $q->whereRaw('1 = 0');
            }
        } elseif ($user->role === 'admin') {
            $q->where('organization_id', $user->organization_id);
        } elseif ($user->role === 'manager') {
            $q->where('department_id', $user->department_id);
        } else {
            $q->whereRaw('1 = 0');
        }

        return $q;
    }

    private function webUsersForUser($user, ?int $organizationId = null)
    {
        $q = User::query()->where('role', '!=', 'superadmin');

        if ($user->role === 'superadmin') {
            $orgId = $organizationId ?? session(SessionController::SESSION_KEY);
            if ($orgId) {
                $q->where('organization_id', $orgId);
            } else {
                $q->whereRaw('1 = 0');
            }
        } elseif ($user->role === 'admin') {
            $q->where('organization_id', $user->organization_id);
        } elseif ($user->role === 'manager') {
            $q->where('department_id', $user->department_id);
        } else {
            $q->whereRaw('1 = 0');
        }

        return $q;
    }

    private function ensureDockAccess($user, Dock $dock): void
    {
        $dock->load('department');
        if ($user->role === 'superadmin') {
            $orgId = session(SessionController::SESSION_KEY);
            if (! $orgId || (int) $dock->department?->organization_id !== (int) $orgId) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'admin') {
            if ((int) $dock->department?->organization_id !== (int) $user->organization_id) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'manager') {
            if ((int) $dock->department_id !== (int) $user->department_id) {
                abort(403);
            }

            return;
        }
        abort(403);
    }

    private function ensureOperatorAccess($user, Operator $operator): void
    {
        if ($user->role === 'superadmin') {
            $orgId = session(SessionController::SESSION_KEY);
            if (! $orgId || (int) $operator->organization_id !== (int) $orgId) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'admin') {
            if ((int) $operator->organization_id !== (int) $user->organization_id) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'manager') {
            if ((int) $operator->department_id !== (int) $user->department_id) {
                abort(403);
            }

            return;
        }
        abort(403);
    }

    private function ensureWebUserAccess($user, User $webUser): void
    {
        if ($user->role === 'superadmin') {
            $orgId = session(SessionController::SESSION_KEY);
            if (! $orgId || (int) $webUser->organization_id !== (int) $orgId) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'admin') {
            if ((int) $webUser->organization_id !== (int) $user->organization_id) {
                abort(403);
            }

            return;
        }
        if ($user->role === 'manager') {
            if ((int) $webUser->department_id !== (int) $user->department_id) {
                abort(403);
            }

            return;
        }
        abort(403);
    }

    /**
     * Recarrega listas (docas / usuários) após troca de empresa na sessão (superadmin).
     */
    public function ajaxLists(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false], 403);
        }
        $request->validate(['organization_id' => 'required|integer|exists:organizations,id']);
        session([SessionController::SESSION_KEY => (int) $request->organization_id]);

        $docks = $this->docksForUser($user, (int) $request->organization_id)->orderBy('name')->get(['id', 'name']);
        $operators = $this->operatorsForUser($user, (int) $request->organization_id)->orderBy('name')->get(['id', 'name', 'email']);
        $users = $this->webUsersForUser($user, (int) $request->organization_id)->orderBy('name')->get(['id', 'name', 'email', 'role']);

        return response()->json([
            'success' => true,
            'docks' => $docks,
            'operators' => $operators,
            'users' => $users,
        ]);
    }
}
