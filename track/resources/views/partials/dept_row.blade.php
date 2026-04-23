@php
    $treeDepth = (int) ($dept->tree_depth ?? 0);
    $padRem = $treeDepth > 0 ? $treeDepth * 1.5 : 0;
@endphp
<tr>
    <td>
        @if($treeDepth > 0)
            <span class="d-inline-block" style="padding-left: {{ $padRem }}rem; border-left: 3px solid var(--primary, #0c5389); min-height: 1.25em;">
                <i class="bi bi-arrow-return-right text-muted" aria-hidden="true"></i>
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
