@if (isset($user) && $user->role === 'superadmin')
<div class="px-3 py-2 border-bottom">
    <style>
        #global-org-select,
        #global-org-select option {
            color: #212529 !important;
            background-color: #fff !important;
        }
    </style>
    <label class="small text-muted mb-1 d-block">Empresa</label>
    <select id="global-org-select" class="form-control form-control-sm" title="Seleção mantida ao navegar" {{ empty($organizations) || $organizations->isEmpty() ? 'disabled' : '' }}>
        @if (!empty($organizations) && $organizations->isNotEmpty())
            @foreach ($organizations as $id => $name)
                <option value="{{ $id }}" {{ $id == ($selectedOrganizationId ?? null) ? 'selected' : '' }}>{{ Str::limit($name, 25) }}</option>
            @endforeach
        @else
            <option value="">Nenhuma empresa cadastrada</option>
        @endif
    </select>
</div>
@endif
