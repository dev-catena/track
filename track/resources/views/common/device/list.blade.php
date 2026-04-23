<div class="row mt-3">

    <div class="col-md-12">
        <div class="card card-body rounded-3">
            <div class="row my-3">
                <div class="col-md-8 my-2">
                    <div class="position-relative">
                        <input type="text" class="form-control" style="padding-left: 2rem;"
                            placeholder="Buscar dispositivos..." id="searchInput">
                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
                    </div>
                </div>
                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="statusFilter">
                        <option value="">All Status </option>
                        <option value="available">Available</option>
                        <option value="inuse">In-Use</option>
                        <option value="maintenance">Maintenance</option>

                    </select>
                </div>
                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="dockFilter">
                        <option value="">All Dock</option>
                        @foreach ($docks as $dock)
                            <option value="{{ $dock->id }}">{{ $dock->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive-sm rounded-3 card card-body border border-primary">
                <table id="device-table" class="table table-rounded">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Build Number</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Dock</th>
                            <th>Return Due</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>




</div>
