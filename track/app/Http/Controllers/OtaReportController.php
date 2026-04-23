<?php

namespace App\Http\Controllers;

use App\Models\OtaActivity;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\SessionController;

class OtaReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $query = OtaActivity::with(['organization:id,name', 'createdBy:id,name,email'])
                ->orderBy('created_at', 'desc');

            if ($user->role === 'admin') {
                $query->where('organization_id', $user->organization_id);
            } elseif ($user->role === 'manager') {
                $dept = \App\Models\Department::find($user->department_id);
                if ($dept) {
                    $query->where('organization_id', $dept->organization_id);
                }
            }

            return DataTables::of($query)
                ->addColumn('organization_name', fn ($row) => $row->organization?->name ?? '-')
                ->addColumn('created_by_name', fn ($row) => $row->createdBy?->name ?? '-')
                ->editColumn('created_at', fn ($row) => $row->created_at?->format('d/m/Y H:i') ?? '-')
                ->rawColumns([])
                ->make(true);
        }

        $organizations = collect();
        if ($user->role === 'superadmin') {
            $organizations = Organization::orderBy('name')->pluck('name', 'id');
        }

        return view('common.ota-report.index', compact('organizations'));
    }
}
