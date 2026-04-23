@extends('layouts.main')
@section('title')
    Mapa da Empresa
@endsection
@section('style')
    <style>
        #company-map-network {
            width: 100%;
            height: 70vh;
            min-height: 500px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .company-map-toolbar {
            margin-bottom: 1rem;
        }
        .vis-tooltip {
            max-width: 320px;
            white-space: pre-line;
        }
        #firmware-select,
        #firmware-select option {
            color: #000 !important;
            background-color: #fff !important;
        }
    </style>
@endsection
@section('content')
    <div class="content">
        <div class="row">
            <div class="col-sm-6 mb-3">
                <h1 class="m-0 d-flex align-items-center" style="text-wrap: nowrap;">
                    <i class="fa-solid fa-sitemap"></i>
                    <span class="heading_title ml-2">Mapa da Empresa</span>
                </h1>
            </div>
            <div class="col-sm-6 mb-3">
                <div class="company-map-toolbar">
                    <label class="mr-2">Empresa:</label>
                    <select id="org-select" class="form-control d-inline-block" style="width: auto;">
                        <option value="">Selecione...</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}" {{ ($selectedOrganizationId ?? null) == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="btn-refresh">
                        <i class="fa fa-refresh"></i> Atualizar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="btn-ping-all" title="Testar conexão das docas">
                        <i class="fa fa-signal"></i> Ping docas
                    </button>
                    <button type="button" class="btn btn-sm btn-success ml-2" id="btn-ota-firmware" title="Atualizar firmware OTA">
                        <i class="fa fa-upload"></i> Atualizar Firmware
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info ml-2" id="btn-upload-firmware" title="Enviar nova versão de firmware">
                        <i class="fa fa-cloud-upload-alt"></i> Subir Firmware
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Grafo da estrutura: Empresa → Departamentos → Docas. Verde = check-in na última hora, laranja = sem check-in na última hora, vermelho = offline.
                        </p>
                        <div id="company-map-network"></div>
                        <div class="mt-2 small text-muted">
                            <span class="mr-3">🟢 Check-in na última hora</span>
                            <span class="mr-3">🟠 Sem check-in na última hora</span>
                            <span class="mr-3">🔴 Offline</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal OTA Firmware --}}
    <div class="modal fade" id="otaModal" tabindex="-1" aria-labelledby="otaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otaModalLabel">
                        <i class="fa fa-upload"></i> Atualizar Firmware (OTA)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Selecione o firmware do repositório para enviar a todas as docas da empresa.</p>
                    <div class="form-group">
                        <label for="firmware-select">Firmware disponível</label>
                        <select class="form-control" id="firmware-select">
                            <option value="">Carregando...</option>
                        </select>
                        <small class="form-text text-muted" id="firmware-info"></small>
                    </div>
                    <div id="ota-count-msg" class="alert alert-info mt-3" style="display:none;">
                        <strong id="ota-count-display">0</strong> dispositivo(s) serão atualizados.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-ota-confirm" disabled>
                        <i class="fa fa-upload"></i> Atualizar todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Upload Firmware --}}
    <div class="modal fade" id="uploadFirmwareModal" tabindex="-1" aria-labelledby="uploadFirmwareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadFirmwareModalLabel">
                        <i class="fa fa-cloud-upload-alt"></i> Subir nova versão de firmware
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="form-upload-firmware">
                    <div class="modal-body">
                        <p class="text-muted mb-3">Envie um arquivo .bin compilado para ESP32. O arquivo será salvo em <code>storage/app/firmware/</code> e poderá ser enviado via OTA.</p>
                        <div class="form-group">
                            <label for="firmware-file">Arquivo .bin</label>
                            <input type="file" class="form-control-file" id="firmware-file" name="firmware" accept=".bin" required>
                            <small class="form-text text-muted">Apenas arquivos .bin. Sugestão de nome: iot-zontec-1.0.2.bin</small>
                        </div>
                        <div id="upload-firmware-error" class="alert alert-danger mt-2" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info" id="btn-upload-firmware-submit">
                            <i class="fa fa-upload"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <script>
        const prefix = "{{ request()->segment(1) }}";
        const dataUrl = "/" + prefix + "/company-map/data";
        const pingUrl = "/" + prefix + "/company-map/ping";
        const firmwareListUrl = "/" + prefix + "/company-map/firmware/list";
        const firmwareUploadUrl = "/" + prefix + "/company-map/firmware/upload";
        const otaCountUrl = "/" + prefix + "/company-map/ota/count";
        const otaTriggerUrl = "/" + prefix + "/company-map/ota/trigger";

        let network = null;
        let currentNodes = [];
        let currentEdges = [];

        const groups = {
            organization: { color: { background: '#5e72e4', border: '#434ee8' }, font: { size: 18 } },
            department: { color: { background: '#11cdef', border: '#0da5c0' }, font: { size: 14 } },
            dock_online: { color: { background: '#2dce89', border: '#24a46d' }, font: { size: 12 } },
            dock_idle: { color: { background: '#fb6340', border: '#ec4a2a' }, font: { size: 12 } },
            dock_offline: { color: { background: '#f5365c', border: '#ec0c38' }, font: { size: 12 } },
        };

        function loadGraph(orgId) {
            if (!orgId) {
                if (network) {
                    network.setData({ nodes: [], edges: [] });
                }
                return;
            }
            fetch(dataUrl + '?organization_id=' + orgId)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    currentNodes = new vis.DataSet(res.nodes);
                    currentEdges = new vis.DataSet(res.edges);
                    if (network) {
                        network.setData({ nodes: currentNodes, edges: currentEdges });
                    } else {
                        initNetwork(currentNodes, currentEdges);
                    }
                })
                .catch(err => console.error(err));
        }

        function initNetwork(nodes, edges) {
            const container = document.getElementById('company-map-network');
            const data = { nodes, edges };
            const options = {
                nodes: { shape: 'box', margin: 10 },
                edges: { arrows: 'to' },
                layout: { hierarchical: { direction: 'UD', sortMethod: 'directed' } },
                physics: false,
                groups: groups,
            };
            network = new vis.Network(container, data, options);
        }

        $('#org-select').on('change', function() {
            const v = $(this).val();
            loadGraph(v);
            if (v && $('#global-org-select').length && selectOrganizationUrl) {
                $.post(selectOrganizationUrl, { organization_id: v, _token: $('meta[name="csrf-token"]').attr('content') });
                $('#global-org-select').val(v);
            }
        });
        $(document).on('organization-changed', function(e, orgId) {
            if (orgId && $('#org-select').val() !== String(orgId)) {
                $('#org-select').val(orgId);
                loadGraph(orgId);
            }
        });

        $('#btn-refresh').on('click', function() {
            const orgId = $('#org-select').val();
            if (orgId) loadGraph(orgId);
        });

        $('#btn-ping-all').on('click', function() {
            const $btn = $(this);
            const orgId = $('#org-select').val();
            if (!orgId) {
                showAlert('warning', 'Selecione uma empresa.');
                return;
            }
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Ping...');
            fetch(dataUrl + '?organization_id=' + orgId)
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.nodes) {
                        showAlert('error', 'Erro ao carregar docas.');
                        return;
                    }
                    const dockNodes = res.nodes.filter(n => n.dock_id);
                    if (dockNodes.length === 0) {
                        showAlert('info', 'Nenhuma doca encontrada para esta empresa.');
                        return;
                    }
                    const promises = dockNodes.map(n => fetch(pingUrl + '/' + n.dock_id).then(r => r.json()));
                    return Promise.all(promises).then(results => {
                        const online = results.filter(r => r.success && r.online).length;
                        const offline = results.filter(r => r.success && !r.online).length;
                        const noIp = results.filter(r => r.message === 'IP não disponível').length;
                        let msg = 'Ping concluído: ';
                        if (online) msg += online + ' online';
                        if (offline) msg += (online ? ', ' : '') + offline + ' offline';
                        if (noIp) msg += (online || offline ? ', ' : '') + noIp + ' sem IP';
                        showAlert(online > 0 ? 'success' : (noIp === dockNodes.length ? 'warning' : 'info'), msg);
                        loadGraph(orgId);
                    });
                })
                .catch(() => showAlert('error', 'Erro ao executar ping.'))
                .finally(() => {
                    $btn.prop('disabled', false).html('<i class="fa fa-signal"></i> Ping docas');
                });
        });

        $('#btn-upload-firmware').on('click', function() {
            $('#upload-firmware-error').hide();
            $('#firmware-file').val('');
            $('#uploadFirmwareModal').modal('show');
        });

        $('#form-upload-firmware').on('submit', function(e) {
            e.preventDefault();
            const fileInput = $('#firmware-file')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                showAlert('warning', 'Selecione um arquivo .bin');
                return;
            }
            const file = fileInput.files[0];
            if (!file.name.toLowerCase().endsWith('.bin')) {
                showAlert('warning', 'Apenas arquivos .bin são permitidos.');
                return;
            }
            const formData = new FormData();
            formData.append('firmware', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            const $btn = $('#btn-upload-firmware-submit');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
            $('#upload-firmware-error').hide();

            fetch(firmwareUploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showAlert('success', res.message);
                    $('#uploadFirmwareModal').modal('hide');
                } else {
                    $('#upload-firmware-error').text(res.message || 'Erro ao enviar.').show();
                }
            })
            .catch(() => {
                $('#upload-firmware-error').text('Erro ao enviar firmware.').show();
            })
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Enviar');
            });
        });

        $('#btn-ota-firmware').on('click', function() {
            const orgId = $('#org-select').val();
            if (!orgId) {
                showAlert('warning', 'Selecione uma empresa.');
                return;
            }
            $('#firmware-select').html('<option value="">Carregando...</option>');
            $('#ota-count-msg').hide();
            $('#btn-ota-confirm').prop('disabled', true);
            $('#otaModal').modal('show');

            fetch(firmwareListUrl)
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data || res.data.length === 0) {
                        $('#firmware-select').html('<option value="">Nenhum firmware encontrado</option>');
                        $('#firmware-info').text('Coloque arquivos .bin em storage/app/firmware/');
                        return;
                    }
                    let opts = '<option value="">Selecione o firmware</option>';
                    res.data.forEach(f => {
                        opts += '<option value="' + f.filename + '" data-version="' + (f.version || '') + '" data-modified="' + (f.modified_at || '') + '">' + f.filename + ' (v' + (f.version || '?') + ' - ' + (f.modified_at || '') + ')</option>';
                    });
                    $('#firmware-select').html(opts);
                })
                .catch(() => {
                    $('#firmware-select').html('<option value="">Erro ao carregar</option>');
                });
        });

        $('#firmware-select').on('change', function() {
            const val = $(this).val();
            const orgId = $('#org-select').val();
            if (!val || !orgId) {
                $('#ota-count-msg').hide();
                $('#btn-ota-confirm').prop('disabled', true);
                return;
            }
            fetch(otaCountUrl + '?organization_id=' + orgId)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        $('#ota-count-display').text(res.count);
                        $('#ota-count-msg').show();
                        $('#btn-ota-confirm').prop('disabled', res.count === 0);
                    }
                });
        });

        $('#btn-ota-confirm').on('click', function() {
            const orgId = $('#org-select').val();
            const filename = $('#firmware-select').val();
            if (!orgId || !filename) return;

            const count = parseInt($('#ota-count-display').text()) || 0;
            if (!confirm('Confirma a atualização OTA em ' + count + ' dispositivo(s)?')) return;

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

            fetch(otaTriggerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ organization_id: orgId, firmware_filename: filename })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showAlert('success', res.message);
                    $('#otaModal').modal('hide');
                } else {
                    showAlert('error', res.message || 'Erro ao enviar OTA');
                }
            })
            .catch(() => showAlert('error', 'Erro ao enviar OTA'))
            .finally(() => {
                $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Atualizar todos');
            });
        });

        @if ($organizations->isNotEmpty() && !empty($selectedOrganizationId))
            loadGraph('{{ $selectedOrganizationId }}');
        @elseif ($organizations->isNotEmpty())
            $('#org-select').val('{{ $organizations->first()->id }}').trigger('change');
        @endif
    </script>
@endsection
