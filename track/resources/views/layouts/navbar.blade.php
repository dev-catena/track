<nav class="navbar navbar-expand-lg navbar-absolute position-fixed d-block">
    <div class="container-fluid">
        <div class="navbar-wrapper">
            <div class="navbar-toggle d-inline">
                <button type="button" class="navbar-toggler">
                    <span class="navbar-toggler-bar bar1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </button>
            </div>
            <a class="navbar-brand dark-logo m-0" href="javascript:void(0)">
                <img src="{{ asset('assets/img/robo-logo-light.png') }}">
            </a>
            <a class="navbar-brand light-logo active m-0" href="javascript:void(0)">
                <img src="{{ asset('assets/img/robo-logo-dark.png') }}">
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
        </button>
        <div class="collapse navbar-collapse" id="navigation">
            <ul class="navbar-nav ml-auto">

                <li class="dropdown nav-item text-center color-change cursor-pointer">
                    <span class="theme-toggle">
                        <i class="fas fa-moon" id="darkIcon"></i>
                        <i class="fas fa-sun d-none" id="lightIcon"></i>
                    </span>
                </li>
                <li class="dropdown nav-item">
                    <a href="#" class="dropdown-toggle nav-link d-flex align-items-center" data-toggle="dropdown">
                        <span class="d-none d-lg-inline-block mr-2 text-nowrap" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">{{ $user?->name ?? '' }}</span>
                        <div class="photo">
                            <img src="{{ ($user?->avatar_url ?? null) ?: asset('assets/img/default-avatar.png') }}" alt="{{ $user?->name ?? 'Perfil' }}" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                        </div>
                        {{-- <b class="caret d-none d-lg-block d-xl-block"></b> --}}
                        <p class="d-lg-none mb-0 ml-2">
                            Sair
                        </p>
                    </a>
                    <ul class="dropdown-menu dropdown-navbar">
                        <li class="nav-link"><a href="{{ url(request()->segment(1) . '/profile') }}"
                                class="nav-item dropdown-item">Perfil</a>
                        </li>

                        <li class="dropdown-divider"></li>
                        <li class="nav-link">
                            <a href="{{ route('logout') }}" class="nav-item dropdown-item"
                                onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                                Sair</a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>

                        </li>
                    </ul>
                </li>
                <li class="separator d-lg-none"></li>
            </ul>
        </div>
    </div>
</nav>
