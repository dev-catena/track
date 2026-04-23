@extends('layouts.main')
@section('title')
    Departamento
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
    </style>
@endsection
@section('content')
    <?php $user = Auth::user(); ?>
    <div class="content">
        <div class="row mb-3">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-buildings"></i>
                    <span class="heading_title ml-2">Departamentos
                    </span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">
                    <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
                        <i class="fa fa-download"></i>&nbsp; Exportar</button>

                    <button class="btn btn-primary float-right " onclick="showForm(1);">
                        <i class="fa fa-plus"></i>&nbsp; Adicionar Departamento</button>
                </div>

                <div class="form_data" style="display: none;">
                    <button class="btn btn-primary float-right form_data" onclick="resetForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Voltar</button>
                </div>

            </div>
        </div>

        <div class="data_list">
            @include('common.department.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('common.department.form')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const departmentSaveUrl = "{{ route('department.store') }}";
        const departmentDetailUrl = "{{ route('department.detail', ':id') }}";
        const departmentUpdateUrl = "{{ route('department.update', ':id') }}";
        const departmentDeleteUrl = "{{ route('department.destroy', ':id') }}";
        const departmentListUrl = "{{ url(request()->segment(1) . '/department') }}";
        const role = "{{ $user->role }}"
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/department.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/department.js')) }}">
    </script>
@endsection
