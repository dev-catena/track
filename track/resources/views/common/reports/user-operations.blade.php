@extends('layouts.main')
@section('title')
    Relatório de operações por usuário
@endsection
@section('style')
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #fff !important;
        }
    </style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-10 mb-3">
                <ul class="nav nav-pills mb-3">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url(request()->segment(1) . '/reports/dock-history') }}">Por doca</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link active">Por usuário</span>
                    </li>
                </ul>
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-person-lines-fill"></i>
                    <span class="heading_title ml-2">Operações por usuário</span>
                </h1>
                <p class="text-muted mt-2 mb-0">
                    <strong>Operador:</strong> check-ins/check-outs no tablet.
                    <strong>Usuário do painel:</strong> registros de atividade no sistema.
                </p>
            </div>
        </div>

        <div class="card card-body rounded-3">
            <div class="row align-items-end">
                @if ($user->role === 'superadmin')
                    <div class="col-md-3 mb-3">
                        <label for="organization_id">Empresa</label>
                        <select id="organization_id" class="form-control">
                            @forelse ($organizations as $oid => $oname)
                                <option value="{{ $oid }}"
                                    {{ (string) $selectedOrganizationId === (string) $oid ? 'selected' : '' }}>
                                    {{ $oname }}</option>
                            @empty
                                <option value="">Nenhuma empresa</option>
                            @endforelse
                        </select>
                    </div>
                @endif
                <div class="col-md-2 mb-3">
                    <label for="mode">Tipo</label>
                    <select id="mode" class="form-control">
                        <option value="operator">Operador</option>
                        <option value="user">Usuário do painel</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="subject_id">Pessoa <span class="text-danger">*</span></label>
                    <select id="subject_id" class="form-control" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="date_from">Data inicial</label>
                    <input type="date" id="date_from" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="date_to">Data final</label>
                    <input type="date" id="date_to" class="form-control">
                </div>
                <div class="col-md-2 mb-3">
                    <button type="button" class="btn btn-primary btn-block" id="btnApplyUserReport">Aplicar</button>
                </div>
            </div>
            <div class="mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportUserCsv">
                    <i class="fa fa-download"></i> Exportar CSV
                </button>
            </div>
            <div id="wrap-table-operator" class="table-responsive">
                <table id="user-ops-operator-table" class="table table-hover table-rounded" style="width:100%">
                    <thead>
                        <tr>
                            <th>Origem</th>
                            <th>Tipo</th>
                            <th>Doca</th>
                            <th>Dispositivo</th>
                            <th>Data/hora</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div id="wrap-table-user" class="table-responsive" style="display:none;">
                <table id="user-ops-user-table" class="table table-hover table-rounded" style="width:100%">
                    <thead>
                        <tr>
                            <th>Origem</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>Descrição</th>
                            <th>IP</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        window.REPORT_OPERATORS = @json($operators);
        window.REPORT_USERS = @json($users);
        const userOpsDataUrl = "{{ url(request()->segment(1) . '/reports/user-operations/data') }}";
        const userOpsExportUrl = "{{ url(request()->segment(1) . '/reports/user-operations/export') }}";
        const ajaxListsUrl = @if ($user->role === 'superadmin') "{{ route('superadmin.reports.ajax-lists') }}" @else "" @endif;
        const csrfToken = "{{ csrf_token() }}";
        const isSuperAdmin = {{ $user->role === 'superadmin' ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('assets/js/ScriptFiles/reports-user-ops.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/reports-user-ops.js')) }}"></script>
@endsection
