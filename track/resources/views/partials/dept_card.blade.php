@php
    $cardDepth = (int) ($dept->tree_depth ?? ($dept->parent_id ? 1 : 0));
    $cardPad = $cardDepth * 1.5;
@endphp
<div class="col-md-4 pb-3 {{ $cardDepth > 0 ? 'dept-child' : '' }}" style="{{ $cardDepth > 0 ? 'margin-left: ' . $cardPad . 'rem; border-left: 3px solid var(--primary, #0c5389);' : '' }}">
    @if($dept->parent_id)
        <small class="text-muted d-block mb-1"><i class="bi bi-arrow-return-right"></i> Filho de {{ optional($dept->parent)->name ?? '—' }}</small>
    @endif
    <div class="card mb-3 " style="height: -webkit-fill-available;">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 title="{{ $dept->name }}" class="title mb-0 text-nowrap card_title_overflow">{{ $dept->name }}
                </h3>
                <div class="dropdown">
                    <button type="button" class="btn btn-link dropdown-toggle btn-icon" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="getDepartmentDetail({{ $dept->id }})">
                            <i class="fa fa-edit"></i>
                            Edit
                        </a>
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="deleteDepartment({{ $dept->id }})">
                            <i class="fa fa-trash"></i>
                            Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <p><i class="bi bi-geo-alt"></i> {{ $dept->location }}</p>
            <p>{{ $dept->description }}</p>

            <div class="card card-body mt-3 p-2 rounded-3">
                <div class="row justify-content-between">
                    <div class="col-6 left">
                        <p class="float-start">
                            <i class="fa-regular fa-hard-drive"></i>
                            {{ $dept->dock_count }} Docks
                        </p>
                    </div>
                    <div class="col-6 right ">
                        <p class="float-end">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                            {{ $dept->device_count }} Devices
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
