<div class="row mt-3">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h6 class=" mb-2 text-nowrap">Total de Atividades</h6>
                            <h2 class="display-2 mb-2">{{ $data->total_activities_count }}</h2>
                        </div>
                        <div class="right  card-icon">
                            <i class="bi bi-activity fa-2x  position-absolute text-primary "></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h6 class=" mb-2 text-nowrap">Today's Activities</h6>
                            <h2 class="display-2 mb-2">{{ $data->todays_activities_count }}</h2>
                        </div>
                        <div class="right  card-icon">
                            <i class="bi bi-calendar-check fa-2x  position-absolute text-success "></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h6 class=" mb-2 text-nowrap">Ações de Dispositivos</h6>
                            <h2 class="display-2 mb-2">{{ $data->device_action_counts }}</h2>
                        </div>
                        <div class="right  card-icon">
                            <i class="bi bi-phone fa-2x  position-absolute "></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card rounded-4">
                    <div class="card-body position-relative">
                        <div class="left">
                            <h6 class=" mb-2 text-nowrap">Ações de Docas</h6>
                            <h2 class="display-2 mb-2">{{ $data->dock_action_counts }}</h2>
                        </div>
                        <div class="right  card-icon">
                            <i class="bi bi-window-dock fa-2x  position-absolute text-info "></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
            <i class="fa fa-download"></i>&nbsp; Export</button>
    </div>
    <div class="col-md-12 mt-3">

        <div class="card card-body rounded-3">
            <div class="row my-3">
                <div class="col-md-{{ $role === 'superadmin' ? 4 : ($role === 'admin' ? 6 : 8) }} my-2">
                    <div class="position-relative">
                        <input type="text" class="form-control" style="padding-left: 2rem;"
                            placeholder="Buscar registros de atividade..." id="searchInput">
                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
                    </div>
                </div>

                @if ($role == 'superadmin')
                    <div class="col-md-2 my-2">
                        <select class="form-control select-control" id="companyFilter">
                            <option value="">All Companies</option>
                            @foreach ($organizations as $org_id => $org_name)
                                <option value="{{ $org_id }}">{{ $org_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 my-2">
                        <select class="form-control select-control" id="departmentFilter">
                            <option value="">All Departments</option>

                        </select>
                    </div>
                @elseif ($role == 'admin')
                    <div class="col-md-2 my-2">
                        <select class="form-control select-control" id="departmentFilter">
                            <option value="">All Departments</option>

                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif



                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="entityTypeFilter">
                        <option value="">All Entity Type</option>
                        {{-- <option value="company">Company</option>
                        <option value="department">Department</option> --}}
                        <option value="dock">Dock</option>
                        <option value="device">Device</option>
                        {{-- <option value="user">User</option> --}}

                    </select>
                </div>
                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="actionFilter">
                        <option value="">All Action</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        {{-- <option value="delete">Delete</option> --}}
                        <option value="assign">Assign</option>
                        <option value="checkin">Check-In</option>
                        <option value="checkout">Check-Out</option>

                    </select>
                </div>
            </div>
            <div class="table-responsive rounded-3 card card-body border border-primary">
                <table id="logs-table" class="table table-rounded">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>User</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>




</div>
