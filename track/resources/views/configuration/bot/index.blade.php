@extends('layouts.main')
@section('title')
    Bots Configuration
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
                    <i class="fa-solid fa-gear"></i>
                    <span class="heading_title ml-2">Configuration</span>

                </h1>
                <h3 class="mb-0 mt-2">Configuração de Bots</h3>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">

                    <button class="btn btn-primary float-right " onclick="showForm(1);">
                        <i class="fa fa-plus"></i>&nbsp; Adicionar Configuração de Bot</button>
                </div>

                <div class="form_data" style="display: none;">
                    <button class="btn btn-primary float-right form_data" onclick="resetForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Back</button>
                </div>

            </div>
        </div>

        <div class="data_list">
            @include('configuration.bot.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('configuration.bot.form')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const botSaveUrl = "{{ route('superadmin.configuration.bot.store') }}";
        const botDetailUrl = "{{ route('superadmin.configuration.bot.detail', ':id') }}";
        const botUpdateUrl = "{{ route('superadmin.configuration.bot.update', ':id') }}";
        const botDeleteUrl = "{{ route('superadmin.configuration.bot.destroy', ':id') }}";
        const botListUrl = "{{ route('superadmin.configuration.bot.index') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/bot_configuration.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/bot_configuration.js')) }}">
    </script>
@endsection
