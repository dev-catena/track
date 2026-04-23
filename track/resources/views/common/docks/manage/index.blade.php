@extends('layouts.main')
@section('title')
    Gestão de Docas
@endsection
@section('style')
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #fff !important;
        }


        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #0c5389 !important;
            border-color: #0c5389 !important;
            color: #fff !important;
            opacity: 0.5;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0c5389 !important;
            border-color: #0c5389 !important;
            color: #fff !important;
        }

        .dataTables_length,
        .dataTables_filter {
            display: none !important;
        }

        table.dataTable tbody th,
        table.dataTable tbody td,
        table.dataTable>thead>tr>th,
        table.dataTable>thead>tr>td {
            background-color: transparent !important;
        }

        table .dropdown-menu:before {
            display: none;
        }

        table .dropdown-menu {
            min-width: 7rem;
        }
    </style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center" style="text-wrap: nowrap;">
                    <i class="fa-regular fa-hard-drive"></i>
                    <span class="heading_title ml-2">Gestão de Docas</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">
                    <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
                        <i class="fa fa-download"></i>&nbsp; Exportar</button>

                    <button class="btn btn-primary float-right " onclick="showForm(1);">
                        <i class="fa fa-plus"></i>&nbsp; Adicionar Doca</button>
                </div>

                <div class="form_data" style="display: none;">
                    <button class="btn btn-primary float-right form_data" onclick="resetForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Voltar</button>
                </div>

            </div>
        </div>

        @if ($user->role === 'superadmin')
            <p class="text-muted small mb-2">
                Todas as docas de todas as empresas são listadas (use a coluna Empresa e a busca).
            </p>
        @endif
        <div class="data_list">
            @include('common.docks.manage.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('common.docks.manage.form')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const authrole = "{{ $user->role }}";
        const dockListUrl = "{{ url(request()->segment(1) . '/dock/management') }}";
        const showOrganizationColumn = {{ $user->role == 'superadmin' ? 'true' : 'false' }};
        const dockSaveUrl = "{{ route('dock.store') }}";
        const dockDetailUrl = "{{ route('dock.detail', ':id') }}";
        const dockUpdateUrl = "{{ route('dock.update', ':id') }}";
        const dockDeleteUrl = "{{ route('dock.destroy', ':id') }}";
        const dockRegeneratePairingUrl = "{{ route('dock.regenerate.pairing', ':id') }}";
        const mqttTopicsUrl = "{{ route('dock.get_mqtt_topics',':id') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/dock.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/dock.js')) }}">
    </script>
@endsection
