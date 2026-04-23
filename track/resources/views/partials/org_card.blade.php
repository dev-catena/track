<div class="col-md-4 pb-3">
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 title="{{ $org->name }}" class="title mb-0 text-nowrap card_title_overflow">{{ $org->name }}
                </h3>
                <div class="dropdown">
                    <button type="button" class="btn btn-link dropdown-toggle btn-icon" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="getOrganizationDetail({{ $org->id }})">
                            <i class="fa fa-edit"></i>
                            Edit
                        </a>
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="deleteOrganization({{ $org->id }})">
                            <i class="fa fa-trash"></i>
                            Delete
                        </a>
                    </div>
                </div>
            </div>

            <p><i class="fa fa-envelope"></i> {{ $org->email }}</p>
            <p class="badge badge-pill badge-primary">{{ $org->plan?->name }}</p>
        </div>

        <div class="card-body">
            <p><i class="fa fa-phone"></i> {{ $org->phone }}</p>
            <p><i class="bi bi-geo-alt-fill"></i> {{ $org->address }}</p>

            <div class="row mt-3">
                <div class="col-6">
                    <div class="card bg-info-opacity mb-3">
                        <div class="card-body p-2">
                            <h4 class="display-4 text-center mb-2">{{ $org->user_count + $org->operator_count }}</h4>
                            <p class="text-center">Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-success-opacity mb-3">
                        <div class="card-body p-2">
                            <h4 class="display-4 text-center mb-2">{{ $org->device_count }}</h4>
                            <p class="text-center">Devices</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="box text-small" style="font-size: 0.74rem;">
                        <p>Max users: 15</p>
                        <p>Max Devices: {{ $org->max_devices }}</p>
                        <p>Created at: {{ $org->created_at }}</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-warning-opacity mb-0">
                        <div class="card-body p-2">
                            <h4 class="display-4 text-center mb-2">{{ $org->dock_count }}</h4>
                            <p class="text-center">Docks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
