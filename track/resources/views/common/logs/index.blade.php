@extends('layouts.main')
@section('title')
    Activity Logs
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
    </style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-activity"></i>
                    <span class="heading_title ml-2">Registros de Atividade</span>
                </h1>
            </div>
            <div class="col-sm-6">

            </div>
        </div>

        <div class="data_list">
            @include('common.logs.list')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const logListUrl = "{{ url(request()->segment(1) . '/logs') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/logs.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/logs.js')) }}">
    </script>
@endsection
