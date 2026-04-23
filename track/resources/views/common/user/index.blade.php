@extends('layouts.main')
@section('title')
    Usuários
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
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-person"></i>
                    <span class="heading_title ml-2">Usuários</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="data_list">
                    <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
                        <i class="fa fa-download"></i>&nbsp; Exportar</button>

                    <button class="btn btn-primary float-right " onclick="showForm(1);">
                        <i class="fa fa-plus"></i>&nbsp; Adicionar Usuário</button>
                </div>

                <div class="form_data " style="display: none;">
                    <button class="btn btn-primary float-right form_data " onclick="resetForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Voltar</button>
                </div>

                <div class="change_password_form_data" style="display: none;">
                    <button class="btn btn-primary float-right change_password_form_data "
                        onclick="resetChangePasswordForm();">
                        <i class="fa fa-arrow-left"></i>&nbsp; Voltar</button>
                </div>

            </div>
        </div>

        <div class="data_list">
            @include('common.user.list')
        </div>
        <div class="form_data" style="display: none;">
            @include('common.user.form')
        </div>
        <div class="change_password_form_data" style="display: none;">
            @include('common.user.change_password')
        </div>

    </div>
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">


        <div class="modal-dialog modal-sm">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code</h5>
                    <button type="button" class="btn-close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="qrCodeContainer" style="padding: 20px;">
                    <!-- SVG will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registerFaceModal" tabindex="-1" aria-labelledby="registerFaceModalLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">


        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registro Facial</h5>
                    <button type="button" class="frm-btn-close btn-close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div class="row">
                        <div class="col-md-12 my-2">
                            <img id="face_image_show" src="" alt="Face Preview" style="display:none;" />
                        </div>
                    </div>
                    <form class="form" id="form1">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="image">Imagem do Rosto<span class="required">*</span></label>
                                <input type="file" class="form-control text-dark" id="image" name="image">
                                <span class="error" id="image_error"></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button onclick="registerUserFace()"; class="btn btn-primary float-right"
                                    id="submitFace_button">
                                    Register</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const userSaveUrl = "{{ route('user.store') }}";
        const userDetailUrl = "{{ route('user.detail', ':id') }}";
        const userUpdateUrl = "{{ route('user.update', ':id') }}";
        const userDeleteUrl = "{{ route('user.destroy', ':id') }}";
        const userListUrl = "{{ url(request()->segment(1) . '/user') }}";
        const userChangePasswordUrl = "{{ route('user.change.password', ':id') }}"

        const operatorDetailUrl = "{{ route('operator.detail', ':id') }}";
        const operatorUpdateUrl = "{{ route('operator.update', ':id') }}";
        const operatorDeleteUrl = "{{ route('operator.destroy', ':id') }}";
        const operatorFaceRegisterUrl = "{{ route('operator.face.register', ':id') }}";
        const operatorFaceDetailUrl = "{{ route('operator.face.detail', ':id') }}";
        const operatorChangePasswordUrl = "{{ route('operator.change.password', ':id') }}";
        const authrole = "{{ $user->role }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/user.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/user.js')) }}">
    </script>
@endsection
