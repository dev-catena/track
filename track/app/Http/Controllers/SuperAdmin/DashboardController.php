<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Device;
class DashboardController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    // Display SuperAdmin dashboard
    public function index()
    {
        $organization_count = Organization::where('status','active')->count('id');
        $inuse_device_count = Device::where('device_status','inuse')->count('id');
        $idle_device_count  = Device::where('device_status','offline')->count('id');


        return view('superadmin.dashboard',compact('organization_count','inuse_device_count','idle_device_count'));
    }
}
