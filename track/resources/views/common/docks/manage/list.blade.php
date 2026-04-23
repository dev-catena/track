<div class="row mt-3">

    <div class="col-md-12">
        <div class="card card-body rounded-3">
            <div class="row my-3">
                <div class="col-md-10 my-2">
                    <div class="position-relative">
                        <input type="text" class="form-control" style="padding-left: 2rem;" placeholder="Buscar docas..."
                            id="searchInput">
                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
                    </div>
                </div>
                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">InActive</option>
                        <option value="maintenance">Maintenance</option>

                    </select>
                </div>

            </div>
            <div class="table-responsive-sm rounded-3 card card-body border border-primary">
                <table id="dock-table" class="table table-rounded">
                    <thead>
                        <tr>
                            @if ($user->role == 'superadmin')
                            <th>Empresa</th>
                            @endif
                            <th>Name</th>
                            <th>MAC</th>
                            <th>Department</th>
                            <th>Localização</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Available</th>
                            <th>Created On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>


                    </tbody>
                </table>
            </div>
        </div>
    </div>




</div>
