<div class="sidebar notranslate" translate="no">
    <div class="sidebar-wrapper">

        <h3 class="my-2 text-center">{{ $user->name }}</h3>
        <hr class="m-0 border">

        @include('partials.global-org-selector')

        @if ($user->role == 'superadmin')
            <ul class="nav">
                <li>
                    <a href="{{ URL('/SuperAdmin/dashboard') }}"
                        class="{{ request()->is('SuperAdmin/dashboard') ? 'active' : '' }}">
                        <i class="tim-icons icon-chart-pie-36"></i>
                        <p>Painel</p>
                    </a>
                </li>
                <li>
                    <a href="{{ URL('/SuperAdmin/organization') }}"
                        class="{{ request()->is('SuperAdmin/organization') ? 'active' : '' }}">
                        <i class="fa-regular fa-building"></i>
                        <p>Empresa</p>
                    </a>
                </li>
                <li>
                    <a href="{{ URL('/SuperAdmin/department') }}"
                        class="{{ request()->is('SuperAdmin/department') ? 'active' : '' }}">
                        <i class="bi bi-buildings"></i>
                        <p>Departamento</p>
                    </a>
                </li>

                <li class="has-submenu {{ request()->is('SuperAdmin/dock*') || request()->is('SuperAdmin/devices/pending') ? 'open' : '' }}">
                    <a href=""
                        class="submenu-toggle {{ request()->is('SuperAdmin/dock*') || request()->is('SuperAdmin/devices/pending') ? 'open active' : '' }}">
                        <i class="fa-regular fa-hard-drive"></i>
                        <p>Docas</p>
                    </a>
                    <ul class="submenu">
                        <li>
                            <a href="{{ URL('/SuperAdmin/dock/management') }}"
                                class="{{ request()->is('SuperAdmin/dock/management') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Gestão de Docas</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/SuperAdmin/dock/panel') }}"
                                class="{{ request()->is('SuperAdmin/dock/panel') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Painel de Docas</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/SuperAdmin/devices/pending') }}"
                                class="{{ request()->is('SuperAdmin/devices/pending') ? 'active' : '' }}"
                                translate="no">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Docas Pendentes</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ request()->is('SuperAdmin/company-map*') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/company-map') }}">
                        <i class="fa-solid fa-sitemap"></i>
                        <p>Mapa da Empresa</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/ota-report*') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/ota-report') }}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Relatório OTA</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/user') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/user') }}">
                        <i class="bi bi-person"></i>
                        <p>Usuários</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/profiles') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/profiles') }}">
                        <i class="bi bi-person-badge"></i>
                        <p>Perfis (referência)</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/permissions') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/permissions') }}">
                        <i class="bi bi-shield-lock"></i>
                        <p>Permissões</p>
                    </a>
                </li>
                <li class="has-submenu {{ request()->is('SuperAdmin/configuration*') ? 'open' : '' }}">
                    <a href=""
                        class="submenu-toggle {{ request()->is('SuperAdmin/configuration*') ? 'open active' : '' }}">
                        <i class="fa-solid fa-gear"></i>
                        <p>Configuração</p>
                    </a>
                    <ul class="submenu">
                        <li>
                            <a href="{{ URL('/SuperAdmin/configuration/bot') }}"
                                class="{{ request()->is('SuperAdmin/configuration/bot') ? 'active' : '' }}">
                                <i class="fa-solid fa-gear"></i>
                                <p>Bots</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/SuperAdmin/configuration/system') }}"
                                class="{{ request()->is('SuperAdmin/configuration/system') ? 'active' : '' }}">
                                <i class="fa-solid fa-gear"></i>
                                <p>Sistema</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ request()->is('SuperAdmin/logs') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/logs') }}">
                        <i class="bi bi-activity"></i>
                        <p>Registros de Atividade</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/reports/*') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/reports/dock-history') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <p>Relatórios</p>
                    </a>
                </li>
                <li class="{{ request()->is('SuperAdmin/notification') ? 'active' : '' }}">
                    <a href="{{ URL('/SuperAdmin/notification') }}" translate="no" class="notranslate">
                        <i class="bi bi-bell"></i>
                        <p class="notranslate">Notificações</p>
                    </a>
                </li>
                {{-- <li>
                    <a href="#">
                        <i class="tim-icons icon-atom"></i>
                        <p>Icons</p>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="tim-icons icon-pin"></i>
                        <p>Maps</p>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="tim-icons icon-bell-55"></i>
                        <p>Notificações</p>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="tim-icons icon-single-02"></i>
                        <p>User Profile</p>
                    </a>
                </li> --}}


            </ul>
        @elseif ($user->role == 'admin')
            <ul class="nav">
                <li class="{{ request()->is('Admin/dashboard') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/dashboard') }}">
                        <i class="tim-icons icon-chart-pie-36"></i>
                        <p>Painel</p>
                    </a>
                </li>
                <li>
                    <a href="{{ URL('/Admin/department') }}"
                        class="{{ request()->is('Admin/department') ? 'active' : '' }}">
                        <i class="bi bi-buildings"></i>
                        <p>Departamento</p>
                    </a>
                </li>
                <li class="has-submenu {{ request()->is('Admin/dock*') || request()->is('Admin/devices/pending') ? 'open' : '' }}">
                    <a href="" class="submenu-toggle {{ request()->is('Admin/dock*') || request()->is('Admin/devices/pending') ? 'open active' : '' }}">
                        <i class="fa-regular fa-hard-drive"></i>
                        <p>Docas</p>
                    </a>
                    <ul class="submenu">
                        <li>
                            <a href="{{ URL('/Admin/dock/management') }}"
                                class="{{ request()->is('Admin/dock/management') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Gestão de Docas</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/Admin/dock/panel') }}"
                                class="{{ request()->is('Admin/dock/panel') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Painel de Docas</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/Admin/devices/pending') }}"
                                class="{{ request()->is('Admin/devices/pending') ? 'active' : '' }}"
                                translate="no">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Docas Pendentes</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ request()->is('Admin/company-map*') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/company-map') }}">
                        <i class="fa-solid fa-sitemap"></i>
                        <p>Mapa da Empresa</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/ota-report*') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/ota-report') }}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Relatório OTA</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/user') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/user') }}">
                        <i class="bi bi-person"></i>
                        <p>Usuários</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/permissions') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/permissions') }}">
                        <i class="bi bi-shield-lock"></i>
                        <p>Permissões</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/notification') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/notification') }}" translate="no" class="notranslate">
                        <i class="bi bi-bell"></i>
                        <p class="notranslate">Notificações</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/configuration') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/configuration') }}">
                        <i class="fa-solid fa-gear"></i>
                        <p>Configuração</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/logs') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/logs') }}">
                        <i class="bi bi-activity"></i>
                        <p>Registros de Atividade</p>
                    </a>
                </li>
                <li class="{{ request()->is('Admin/reports/*') ? 'active' : '' }}">
                    <a href="{{ URL('/Admin/reports/dock-history') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <p>Relatórios</p>
                    </a>
                </li>
            </ul>
        @elseif ($user->role == 'manager')
            <ul class="nav">
                <li class="{{ request()->is('Manager/dashboard') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/dashboard') }}">
                        <i class="tim-icons icon-chart-pie-36"></i>
                        <p>Painel</p>
                    </a>
                </li>
                <li class="has-submenu {{ request()->is('Manager/dock*') ? 'open' : '' }}">
                    <a href="" class="submenu-toggle {{ request()->is('Manager/dock*') ? 'open active' : '' }}">
                        <i class="fa-regular fa-hard-drive"></i>
                        <p>Docas</p>
                    </a>
                    <ul class="submenu">
                        <li>
                            <a href="{{ URL('/Manager/dock/management') }}"
                                class="{{ request()->is('Manager/dock/management') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Gestão de Docas</p>
                            </a>
                        </li>
                        <li>
                            <a href="{{ URL('/Manager/dock/panel') }}"
                                class="{{ request()->is('Manager/dock/panel') ? 'active' : '' }}">
                                <i class="fa-regular fa-hard-drive"></i>
                                <p>Painel de Docas</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ request()->is('Manager/company-map*') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/company-map') }}">
                        <i class="fa-solid fa-sitemap"></i>
                        <p>Mapa da Empresa</p>
                    </a>
                </li>
                <li class="{{ request()->is('Manager/ota-report*') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/ota-report') }}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Relatório OTA</p>
                    </a>
                </li>
                <li class="{{ request()->is('Manager/user') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/user') }}">
                        <i class="bi bi-person"></i>
                        <p>Usuários</p>
                    </a>
                </li>

                <li class="{{ request()->is('Manager/configuration') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/configuration') }}">
                        <i class="fa-solid fa-gear"></i>
                        <p>Configuração</p>
                    </a>
                </li>
                <li class="{{ request()->is('Manager/logs') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/logs') }}">
                        <i class="bi bi-activity"></i>
                        <p>Registros de Atividade</p>
                    </a>
                </li>
                <li class="{{ request()->is('Manager/reports/*') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/reports/dock-history') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <p>Relatórios</p>
                    </a>
                </li>
                <li class="{{ request()->is('Manager/notification') ? 'active' : '' }}">
                    <a href="{{ URL('/Manager/notification') }}" translate="no" class="notranslate">
                        <i class="bi bi-bell"></i>
                        <p class="notranslate">Notificações</p>
                    </a>
                </li>
            </ul>
        @endif
    </div>
</div>

<script>
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            if (submenu) {
                if (submenu.style.display === "block") {
                    submenu.style.display = "none";
                    parent.classList.remove('open');
                } else {
                    submenu.style.display = "block";
                    parent.classList.add('open');
                }
            }
        });
    });
</script>
