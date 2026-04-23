@extends('layouts.main')
@section('title')
    Empresa
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
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="fa-regular fa-building"></i>
                    <span class="heading_title ml-2">Empresas</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                {{-- <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard v1</li>
                </ol> --}}
            </div>
        </div>

        <div class="data_list">
            @include('superadmin.organization.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('superadmin.organization.form')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const organizationSaveUrl = "{{ route('superadmin.organization.store') }}";
        const organizationDetailUrl = "{{ route('superadmin.organization.detail', ':id') }}";
        const organizationUpdateUrl = "{{ route('superadmin.organization.update', ':id') }}";
        const organizationDeleteUrl = "{{ route('superadmin.organization.destroy', ':id') }}";
        const organizationListUrl = "{{ route('superadmin.organization.index') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/SuperAdmin/organization.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/SuperAdmin/organization.js')) }}">
    </script>
@endsection
