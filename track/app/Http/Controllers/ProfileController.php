<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\ProfileInterface;
use Yajra\DataTables\Facades\DataTables;

class ProfileController extends Controller
{
    protected $profile;

    public function __construct(ProfileInterface $profile)
    {
        $this->profile = $profile;
    }

    public function index(Request $request)
    {
        if ($request->ajax() || $request->has('draw')) {
            $search = $request->input('search.value', '');
            $data = $this->profile->all($search);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('details', function ($row) {
                    return view('partials.profile_card', ['profile' => $row])->render();
                })
                ->rawColumns(['details'])
                ->make(true);
        }

        return view('common.profile_crud.index');
    }
}
