<div class="col-md-4 pb-3">
    <div class="card mb-3">
        <div class="card-header">
            <h3 title="{{ $profile->name }}" class="title mb-0 text-nowrap card_title_overflow">{{ $profile->name }}</h3>
            <div class="badge-section mt-2">
                @if($profile->code)
                    <span class="badge badge-secondary rounded-4 p-2 mr-1">Código: {{ $profile->code }}</span>
                @endif
                @if($profile->requires_username)
                    <span class="badge badge-info rounded-4 p-2">Requer usuário</span>
                @endif
                @if($profile->is_operator)
                    <span class="badge badge-primary rounded-4 p-2">Operador</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <p class="small mb-0 text-muted">Ordem: {{ $profile->sort_order ?? 0 }}</p>
            @if($profile->assignable_by)
                <p class="small mb-0 text-muted mt-1">Atribuível por: {{ $profile->assignable_by }}</p>
            @endif
        </div>
    </div>
</div>
