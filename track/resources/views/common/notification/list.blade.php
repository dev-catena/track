<div class="row mt-3">


    <div class="col-md-12 mt-3">
        <div class="card card-body rounded-3">
            <div class="row my-3">
                <div class="col-md-12 my-2">
                    <div class="position-relative">
                        <input type="text" class="form-control" style="padding-left: 2rem;"
                            placeholder="Buscar notificações..." id="searchInput">
                        <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
                    </div>
                </div>
                {{-- <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="entityTypeFilter">
                        <option value="">All Entity Type</option>

                    </select>
                </div>
                <div class="col-md-2 my-2">
                    <select class="form-control select-control" id="actionFilter">
                        <option value="">All Action</option>

                    </select>
                </div> --}}
            </div>
            <div class="table-responsive-sm rounded-3 card card-body border border-primary">
                <table id="notification-table" class="table table-rounded">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Device</th>
                            <th>User</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>




</div>
