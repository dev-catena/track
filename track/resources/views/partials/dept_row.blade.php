<tr>
    <td>
        @if($dept->parent_id)
            <span style="padding-left: 1.5rem; border-left: 3px solid var(--primary, #0c5389); display: inline-block;">
                <i class="bi bi-arrow-return-right text-muted"></i>
                {{ $dept->name }}
            </span>
        @else
            <strong>{{ $dept->name }}</strong>
        @endif
    </td>
    <td>{{ $dept->location ?? '—' }}</td>
    <td>{{ optional($dept->parent)->name ?? '—' }}</td>
    <td>{{ $dept->dock_count ?? 0 }}</td>
    <td>{{ $dept->device_count ?? 0 }}</td>
    <td>
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-link dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-ellipsis-h"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="javascript:void(0);" onclick="getDepartmentDetail({{ $dept->id }})">
                    <i class="fa fa-edit"></i> Editar
                </a>
                <a class="dropdown-item" href="javascript:void(0);" onclick="deleteDepartment({{ $dept->id }})">
                    <i class="fa fa-trash"></i> Excluir
                </a>
            </div>
        </div>
    </td>
</tr>
