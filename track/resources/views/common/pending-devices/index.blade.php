@extends('layouts.main')
@section('title')
    Docas Pendentes
@endsection
@section('style')
<style>
    /* Tabela Docas Pendentes - fundo escuro para contraste (evita fundo branco) */
    .pending-devices-table-wrapper,
    .pending-devices-table-wrapper.card-body,
    .pending-devices-table-wrapper #pending-devices-table,
    .pending-devices-table-wrapper #pending-devices-table thead,
    .pending-devices-table-wrapper #pending-devices-table tbody,
    .pending-devices-table-wrapper #pending-devices-table tbody tr,
    .pending-devices-table-wrapper #pending-devices-table th,
    .pending-devices-table-wrapper #pending-devices-table td {
        background-color: #27293d !important;
    }
    .pending-devices-table-wrapper #pending-devices-table th,
    .pending-devices-table-wrapper #pending-devices-table td,
    .pending-devices-table-wrapper #pending-devices-table code,
    .pending-devices-table-wrapper .text-muted {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    .pending-devices-table-wrapper #pending-devices-table th,
    .pending-devices-table-wrapper #pending-devices-table td {
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
</style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center" style="text-wrap: nowrap;">
                    <i class="fa-regular fa-hard-drive"></i>
                    <span class="heading_title ml-2">Docas Pendentes</span>
                </h1>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card card-body rounded-3">
                    <p class="text-muted mb-3">
                        O ESP32 é a doca. Ao registrar o ESP32 na rede, você está registrando a doca. Docas pendentes aguardam ativação (empresa/departamento) e depois aparecem em <strong>Gestão de Docas</strong>. (Tablets são cadastrados separadamente em Gestão de Dispositivos.)
                    </p>
                    <p class="text-muted small">
                        <strong>Lista vazia:</strong> se a doca já foi ativada antes, ela <strong>não</strong> fica aqui; veja <strong>Gestão de Docas</strong> ({{ url(request()->segment(1) . '/dock/management') }}). Só entram pendentes com status &quot;Pendente&quot; no banco.
                    </p>
                    <div class="alert alert-info mb-3">
                        <strong>Doca já ativada não aparece aqui.</strong> Se o ESP32 retorna "Doca online" (deployed), ele já foi ativado antes.
                        Para reaparecer e poder registrar de novo: use <strong>Reverter para pendente</strong> com o MAC da doca (ex: B0:CB:D8:8B:80:BC).
                    </div>
                    <div class="card card-body mb-3 bg-light">
                        <h6><i class="fa fa-undo"></i> Reverter doca ativada para pendente</h6>
                        <form id="revert-form" class="form-inline">
                            <input type="text" id="revert-mac" class="form-control form-control-sm mr-2" placeholder="MAC (ex: B0:CB:D8:8B:80:BC)" style="min-width: 180px;">
                            <button type="submit" class="btn btn-sm btn-warning"><i class="fa fa-undo"></i> Reverter</button>
                        </form>
                        <small class="text-muted mt-1">A doca voltará a aparecer aqui na próxima conexão do ESP32.</small>
                    </div>
                    <div class="table-responsive-sm rounded-3 card card-body border border-primary pending-devices-table-wrapper" style="background-color: #27293d;">
                        <table class="table table-rounded" id="pending-devices-table" style="background-color: #27293d; color: rgba(255,255,255,0.9);">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>MAC</th>
                                    <th>IP</th>
                                    <th>WiFi</th>
                                    <th>Status</th>
                                    <th>Registrado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pending as $device)
                                    <tr data-id="{{ $device->id }}">
                                        <td>{{ $device->device_name }}</td>
                                        <td><code>{{ $device->mac_address }}</code></td>
                                        <td>{{ $device->ip_address ?? '-' }}</td>
                                        <td>{{ $device->wifi_ssid ?? '-' }}</td>
                                        <td>
                                            @if ($device->status === 'pending')
                                                <span class="badge bg-warning text-dark">Pendente</span>
                                            @else
                                                <span class="badge bg-success">Ativado</span>
                                                @if ($device->mqttTopic)
                                                    <br><small class="text-muted">{{ $device->mqttTopic->name }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $device->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>
                                            @if ($device->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-primary btn-activate"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name }}"
                                                    data-mac="{{ $device->mac_address }}">
                                                    <i class="fa fa-check"></i> Ativar
                                                </button>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Nenhuma doca pendente no momento.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Ativar --}}
    <div class="modal fade" id="activateModal" tabindex="-1" aria-labelledby="activateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activateModalLabel">Ativar Doca</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Ativando: <strong id="activate-device-name"></strong><br>
                        <small class="text-muted" id="activate-device-mac"></small>
                    </p>
                    <form id="activate-form">
                        <input type="hidden" id="activate-device-id" name="device_id">
                        @if ($user->role === 'superadmin')
                        <div class="mb-3">
                            <label for="activate-organization" class="form-label">Empresa <span class="text-danger">*</span></label>
                            <select class="form-select" id="activate-organization" name="organization" required>
                                <option value="">Selecione a empresa</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}" {{ ($selectedOrganizationId ?? null) == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @if ($organizations->isEmpty())
                                <small class="text-warning">Nenhuma empresa ativa cadastrada.</small>
                            @endif
                        </div>
                        @endif
                        <div class="mb-3">
                            <label for="activate-department" class="form-label">Departamento / Local <span class="text-danger">*</span></label>
                            <select class="form-select" id="activate-department" name="department" required>
                                <option value="">Selecione o departamento</option>
                                @if ($user->role !== 'superadmin')
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($user->role === 'superadmin')
                                <small class="text-muted">Selecione a empresa primeiro para carregar os departamentos.</small>
                            @elseif ($departments->isEmpty())
                                <small class="text-warning">Nenhum departamento ativo cadastrado.</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="activate-device-type" class="form-label">Tipo (ESP32/doca)</label>
                            <select class="form-select" id="activate-device-type" name="device_type">
                                <option value="1">ESP32 / IoT</option>
                                <option value="2">Outro</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-activate">
                        <i class="fa fa-check"></i> Ativar Doca
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const activateUrl = "{{ route('devices.pending.activate', ':id') }}";
        const revertUrl = "{{ route('devices.pending.revert') }}";
        const deptByCompanyUrl = "{{ route('department_list_by_company', ':id') }}";
        const isSuperAdmin = {{ $user->role === 'superadmin' ? 'true' : 'false' }};

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            @if ($user->role === 'superadmin')
            $('#activate-organization').on('change', function() {
                const orgId = $(this).val();
                const $dept = $('#activate-department');
                $dept.html('<option value="">Carregando...</option>');
                if (!orgId) {
                    $dept.html('<option value="">Selecione a empresa primeiro</option>');
                    return;
                }
                $.get(deptByCompanyUrl.replace(':id', orgId), function(res) {
                    let opts = '<option value="">Selecione o departamento</option>';
                    if (res.status == 1 && res.data && res.data.length) {
                        res.data.forEach(function(d) {
                            opts += '<option value="' + d.id + '">' + d.name + '</option>';
                        });
                    }
                    $dept.html(opts);
                }).fail(function() {
                    $dept.html('<option value="">Erro ao carregar</option>');
                });
            });
            @endif

            $('.btn-activate').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const mac = $(this).data('mac');

                $('#activate-device-id').val(id);
                $('#activate-device-name').text(name);
                $('#activate-device-mac').text('MAC: ' + mac);
                $('#activate-department').val('');
                $('#activate-device-type').val('1');
                @if ($user->role === 'superadmin')
                var globalOrg = $('#global-org-select').val();
                $('#activate-organization').val(globalOrg || '');
                $('#activate-department').html('<option value="">Carregando...</option>');
                if (globalOrg) {
                    $('#activate-organization').trigger('change');
                } else {
                    $('#activate-department').html('<option value="">Selecione a empresa primeiro</option>');
                }
                @endif

                $('#activateModal').modal('show');
            });

            $('#btn-confirm-activate').on('click', function() {
                const deviceId = $('#activate-device-id').val();
                const deviceType = $('#activate-device-type').val();
                const department = $('#activate-department').val();
                const organization = $('#activate-organization').val();

                if (!department) {
                    showAlert('warning', 'Selecione o departamento.');
                    return;
                }
                if (isSuperAdmin && !organization) {
                    showAlert('warning', 'Selecione a empresa.');
                    return;
                }

                const postData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    device_type: parseInt(deviceType),
                    department: parseInt(department)
                };
                if (isSuperAdmin) {
                    postData.organization = parseInt(organization);
                }

                const url = activateUrl.replace(':id', deviceId);
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Ativando...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: postData,
                    success: function(response) {
                        if (response.status == 1) {
                            showAlert('success', response.message);
                            $('#activateModal').modal('hide');
                            location.reload(); // doca sai da lista (só pendentes)
                        } else {
                            showAlert('error', response.message || 'Erro ao ativar.');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Erro ao ativar.';
                        showAlert('error', msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa fa-check"></i> Ativar Doca');
                    }
                });
            });

            $('#revert-form').on('submit', function(e) {
                e.preventDefault();
                const mac = $('#revert-mac').val().trim();
                if (!mac) {
                    showAlert('warning', 'Informe o MAC da doca.');
                    return;
                }
                const $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Revertendo...');
                $.ajax({
                    url: revertUrl,
                    type: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), mac_address: mac },
                    success: function(response) {
                        if (response.status == 1) {
                            showAlert('success', response.message);
                            $('#revert-mac').val('');
                            location.reload();
                        } else {
                            showAlert('error', response.message || 'Erro ao reverter.');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Erro ao reverter.';
                        showAlert('error', msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa fa-undo"></i> Reverter');
                    }
                });
            });
        });
    </script>
@endsection
