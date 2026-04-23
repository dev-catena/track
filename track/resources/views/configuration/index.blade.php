@extends('layouts.main')
@section('title')
    System Configuration
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="fa-solid fa-gear"></i>
                    <span class="heading_title ml-2">Configuration</span>
                </h1>
                <h3 class="mb-0 mt-2">Configuração do Sistema</h3>
            </div>
            <div class="col-sm-6">
            </div>
        </div>

        <div class="form_data">
            @include('configuration.form')
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const systemSettingUpdateUrl = "{{ route('configuration.system.update', ':id') }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/system_configuration.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/system_configuration.js')) }}">
    </script>
@endsection
