@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/img/robo-logo-light.png') }}" alt="Logo" style="width: 50%;">
                </div>
                <form method="POST" action="{{ route('password.update') }}">
                    <div class="card rounded-5">


                        <div class="card-body px-0 pb-2">

                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="row">

                                <div class="col-md-12">
                                    <input id="email" type="hidden"
                                        class="form-control @error('email') is-invalid @enderror" name="email"
                                        value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                                        placeholder="Email Address">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                {{-- <label for="password"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label> --}}

                                <div class="col-md-12">
                                    <input id="password" type="password"
                                        class="form-control border-0 @error('password') is-invalid @enderror"
                                        name="password" required autocomplete="new-password" placeholder="Password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <hr class="m-0" style="border : 1px solid #000;">
                            <div class="row">
                                {{-- <label for="password-confirm"
                                    class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label> --}}

                                <div class="col-md-12">
                                    <input id="password-confirm" type="password" class="form-control border-0"
                                        name="password_confirmation" required autocomplete="new-password"
                                        placeholder="Confirm Password">
                                </div>
                            </div>



                        </div>
                    </div>
                    <div class="row mb-0 text-center">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Set Password') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
