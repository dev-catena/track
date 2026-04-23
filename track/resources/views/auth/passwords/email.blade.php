@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/img/robo-logo-light.png') }}" alt="Logo" style="width: 50%;">
                </div>
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="card rounded-5 mb-3">
                        {{-- <div class="card-header">
                        <h3 class="fw-bold text-center my-2">{{ __('Reset Password') }}</h3>
                    </div> --}}

                        <div class="card-body p-1">




                            <div class="row">
                                {{-- <label for="email"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label> --}}

                                <div class="col-md-12">
                                    <input id="email" type="email"
                                        class="form-control border-0 @error('email') is-invalid @enderror" name="email"
                                        value="{{ old('email') }}" required autocomplete="email" autofocus
                                        placeholder="Email Address">

                                    @error('email')
                                        <span class="invalid-feedback pl-2 pb-2" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>



                        </div>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="row mb-0 text-center">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Send Password Reset Link') }}
                            </button>
                        </div>
                        @if (Route::has('login'))
                            <a class="btn btn-link" href="{{ route('login') }}">
                                {{ __('Back to Login?') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
