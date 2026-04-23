@extends('layouts.main')
@section('title')
    Relatório por doca
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
                        <span class="nav-link active">Por doca</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url(request()->segment(1) . '/reports/user-operations') }}">Por usuário</a>
                    </li>
                </ul>
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-window-dock"></i>
                    <span class="heading_title ml-2">Histórico da doca</span>
                </h1>
                <p class="text-muted mt-2 mb-0">Check-ins e check-outs de dispositivos vinculados à doca (filtro por período).</p>
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
                <div class="col-md-3 mb-3">
                    <label for="dock_id">Doca <span class="text-danger">*</span></label>
                    <select id="dock_id" class="form-control" required>
                        <option value="">Selecione...</option>
                        @foreach ($docks as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
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
                    <button type="button" class="btn btn-primary btn-block" id="btnApplyDockReport">
                        Aplicar
                    </button>
                </div>
            </div>
            <div class="mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportDockCsv">
                    <i class="fa fa-download"></i> Exportar CSV
                </button>
            </div>
            <div class="table-responsive">
                <table id="dock-history-table" class="table table-hover table-rounded">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Operador</th>
                            <th>E-mail</th>
                            <th>Dispositivo</th>
                            <th>Data/hora</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const prefix = "{{ request()->segment(1) }}";
        const dockReportDataUrl = "{{ url(request()->segment(1) . '/reports/dock-history/data') }}";
        const dockReportExportUrl = "{{ url(request()->segment(1) . '/reports/dock-history/export') }}";
        const ajaxListsUrl = @if ($user->role === 'superadmin') "{{ route('superadmin.reports.ajax-lists') }}" @else "" @endif;
        const csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{{ asset('assets/js/ScriptFiles/reports-dock.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/reports-dock.js')) }}"></script>
@endsection
