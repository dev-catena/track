@extends('layouts.main')
@section('title')
    Profile
@endsection

@section('content')
    <?php $user = Auth::user(); ?>
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center">
                    <i class="bi bi-person"></i>
                    <span class="heading_title ml-2">Profile</span>
                </h1>
            </div>

        </div>
        <div class="form_data">
            @include('common.profile.form')
        </div>
        <div class="change_password_form_data">
            @include('common.user.change_password')
        </div>

    </div>
@endsection
@section('scripts')
    <script>
        const authrole = "{{ $user->role }}";
    </script>
    <script
        src="{{ asset('assets/js/ScriptFiles/user.js') }}?v={{ filemtime(public_path('assets/js/ScriptFiles/user.js')) }}">
    </script>
@endsection
