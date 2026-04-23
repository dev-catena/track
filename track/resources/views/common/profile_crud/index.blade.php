@extends('layouts.main')
@section('title') Perfis do sistema @endsection
@section('style')
<style>
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { color: #fff !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover { background: #0c5389 !important; opacity: 0.5; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #0c5389 !important; color: #fff !important; }
</style>
@endsection
@section('content')
<div class="content">
    <div class="row mb-3">
        <div class="col-12 mb-3">
            <h1 class="m-0 d-flex align-items-center">
                <i class="bi bi-person-badge"></i>
                <span class="heading_title ml-2">Perfis do sistema</span>
            </h1>
            <div class="alert alert-info mt-3 mb-0" role="alert">
                <strong>Consulta.</strong> Os perfis são definidos pelo sistema (seed/migração) e ligam-se ao papel (<code>role</code>) dos usuários.
                Não é possível criar perfis arbitrários pela interface. Para alterar <strong>o que cada perfil pode acessar</strong>, use
                <a href="{{ url(request()->segment(1) . '/permissions') }}" class="alert-link">Permissões</a>.
            </div>
        </div>
    </div>
    <div class="data_list">@include('common.profile_crud.list')</div>
</div>
@endsection
@section('scripts')
<script>
const profileListUrl = "{{ url(request()->segment(1) . '/profiles') }}";
</script>
<script src="{{ asset('assets/js/ScriptFiles/profile.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/profile.js')) }}"></script>
@endsection
