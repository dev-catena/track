@extends('layouts.main')
@section('title')
    Atualizações OTA
@endsection
@section('style')
<style>
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { color: #fff !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover { background: #0c5389 !important; opacity: 0.5; }
.dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #0c5389 !important; color: #fff !important; }
.dataTables_length, .dataTables_filter { display: none !important; }
</style>
@endsection
@section('content')
<?php $user = Auth::user(); ?>
<div class="content">
    <div class="row mb-3">
        <div class="col-sm-6 mb-3">
            <h1 class="m-0 d-flex align-items-center">
                <i class="fa fa-cloud-upload-alt"></i>
                <span class="heading_title ml-2">Atualizações OTA</span>
            </h1>
        </div>
        <div class="col-sm-6 mb-3">
            <a href="{{ URL(request()->segment(1) . '/company-map') }}" class="btn btn-outline-primary float-right">
                <i class="fa fa-sitemap"></i> Mapa da Empresa
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3">
                Histórico de atualizações de firmware OTA disparadas pelo sistema.
            </p>
            <div class="table-responsive">
                <table id="ota-table" class="table table-bordered">
                    <thead>
                        <tr>
                            @if($user->role == 'superadmin')
                            <th>Empresa</th>
                            @endif
                            <th>Firmware</th>
                            <th>Versão</th>
                            <th>Enviados</th>
                            <th>Falhas</th>
                            <th>Disparado por</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
const otaListUrl = "{{ url(request()->segment(1) . '/ota-report') }}";
const showOrgColumn = {{ $user->role == 'superadmin' ? 'true' : 'false' }};
$(function() {
    var cols = [
        { data: 'firmware_filename', name: 'firmware_filename' },
        { data: 'firmware_version', name: 'firmware_version' },
        { data: 'sent', name: 'sent' },
        { data: 'failed', name: 'failed' },
        { data: 'created_by_name', name: 'created_by_name' },
        { data: 'created_at', name: 'created_at' }
    ];
    if (showOrgColumn) cols.unshift({ data: 'organization_name', name: 'organization_name' });
    $('#ota-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: otaListUrl,
        columns: cols,
        order: [[cols.length - 1, 'desc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' }
    });
});
</script>
@endsection
