<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<?php $user = Auth::user(); ?>
@include('layouts.header')

<body class="{{ $theme }}">
    <div id="loader">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <div id="app">
        <div class="wrapper">
            @include('layouts.sidebar')
            <div class="main-panel">
                @include('layouts.navbar')

                @yield('content')

                @include('layouts.footer')

            </div>
        </div>
    </div>
    @include('layouts.scripts')
</body>

</html>
