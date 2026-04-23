@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/img/robo-logo-light.png') }}" alt="Logo" style="width: 50%;">
                </div>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="card rounded-5 mb-3">
                        {{-- <div class="card-header">
                        <h3 class="fw-bold text-center my-2">{{ __('Login') }}</h3>
                    </div> --}}

                        <div class="card-body px-0 pb-2">


                            <div class="row">
                                {{-- <label for="email"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label> --}}

                                <div class="col-md-12">
                                    <div class="d-inline-flex align-items-start pl-3 w-100 flex-column">
                                        <div class="d-flex align-items-center w-100">
                                            <i class="fas fa-user-circle"></i>
                                            <input id="email" type="email"
                                                class="form-control pl-2 border-0 @error('email') is-invalid @enderror"
                                                name="email" value="{{ old('email') }}" required autocomplete="email"
                                                autofocus placeholder="E-mail">
                                        </div>
                                        @error('email')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                </div>
                            </div>

                            <hr class="m-0" style="border : 1px solid #000;">

                            <div class="row">
                                {{-- <label for="password"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label> --}}

                                <div class="col-md-12">
                                    <div class="d-inline-flex align-items-start pl-3 w-100 flex-column">
                                        <div class="d-flex align-items-center w-100">
                                            <i class="fa fa-lock"></i>
                                            <input id="password" type="password"
                                                class="form-control pl-2 border-0 @error('password') is-invalid @enderror"
                                                name="password" required autocomplete="current-password"
                                                placeholder="Senha">
                                        </div>
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="row mb-3">
                                <div class="col-md-6 offset-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div> --}}


                        </div>


                    </div>
                    <div class="row mb-0 text-center">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Login') }}
                            </button>


                        </div>
                        @if (Route::has('password.request'))
                            <a class="btn btn-link" href="{{ route('password.request') }}">
                                {{ __('Esqueceu sua senha?') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
