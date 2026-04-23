<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">
                        @if ($user->role == 'superadmin')
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="organization_id">Empresa <span class="required">*</label>
                                    <select class="form-control select-control" id="organization_id"
                                        name="organization_id" onchange="getDepartmentListByCompanyId(this.value)">
                                        <option value="">Selecionar Empresa</option>
                                        @foreach ($organizations as $id => $name)
                                            <option value="{{ $id }}" {{ ($selectedOrganizationId ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach

                                    </select>
                                    <span class="error" id="organization_error"></span>
                                </div>
                            </div>
                        @else
                            <select class="form-control select-control d-none" id="organization_id"
                                name="organization_id" onchange="getDepartmentListByCompanyId(this.value)">
                                <option value="">Selecionar Empresa</option>
                                <option value="{{ $organizations }}" selected>$organizations</option>
                            </select>
                            <input type="hidden" name="organization_id" id="organization_id"
                                value="{{ $organizations }}">
                            <span class="error" id="organization_error"></span>
                        @endif

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Enter dock Name">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="capacity">Capacity</label>
                                <input type="text" class="form-control" id="capacity" name="capacity"
                                    placeholder="Capacity">
                                <span class="error" id="capacity_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location">Localização</label>
                                <input type="text" class="form-control" id="location" name="location"
                                    placeholder="Localização">
                                <span class="error" id="location_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 ">

                            <div class="form-group">
                                <label for="dock_number">Serial da Doca</label>
                                <input type="text" class="form-control" id="dock_number" name="dock_number"
                                    placeholder="Serial Dock">
                                <span class="error" id="dock_number_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <label for="status">Status <span class="required">*</span></label>
                            <select class="form-control select-control" id="status" name="status">
                                <option value="active" selected>Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="maintenance">Manutenção</option>
                            </select>
                            <span class="error" id="status_error"></span>

                        </div>
                        @if ($user->role == 'superadmin' || $user->role == 'admin')
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department_id">Departamento <span class="required">*</label>
                                    <select class="form-control select-control" id="department_id" name="department_id">
                                        <option value="">Selecionar Departamento</option>
                                        @if ($user->role == 'admin')
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <span class="error" id="department_error"></span>
                                </div>
                            </div>
                        @else
                            <div class="col-md-6 d-none">
                                <select class="form-control select-control " id="department_id" name="department_id">
                                    <option value="{{ $departments }}" selected>{{ $departments }}</option>
                                </select>
                                <span class="error" id="department_error"></span>
                            </div>

                        @endif
                        <div class="col-md-12 mb-3" id="pairing-code-section" style="display:none;">
                            <div class="alert alert-info">
                                <div class="mb-2" id="mac-display-row" style="display:none;">
                                    <strong>MAC (etiquetar na doca):</strong>
                                    <code id="mac-display" class="font-weight-bold ml-1" style="font-size:1.1em;"></code>
                                    <br><small class="text-muted">O colaborador do setor usa este MAC para associar o tablet à doca física.</small>
                                </div>
                                <div class="mb-2">
                                    <strong>Código para Tablet (alternativa):</strong>
                                    <span id="pairing-code-display" class="font-weight-bold" style="font-size:1.2em; letter-spacing:2px;"></span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="btn-regenerate-pairing">Regenerar</button>
                                    <br><small>Configure no app do tablet (lista por MAC ou digite este código).</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <label for="active_device">Dispositivos Ativos (Tópico MQTT) <span class="required">*</span></label>
                            <select class="form-control select-control" id="active_device" name="active_device">
                                <option value="">Selecionar Dispositivo Ativo</option>
                            </select>
                            <small class="form-text text-muted">Tópicos MQTT. Ative em Docas Pendentes antes de associar.</small>
                            <span class="error" id="active_device_error"></span>

                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="form-group">
                                <label for="description">Descrição</label>
                                <textarea rows="3" class="form-control" id="description" name="description" autocomplete="off"
                                    placeholder="Descrição"></textarea>
                                <span class="error" id="description_error"></span>
                            </div>
                        </div>
                    </div>


                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="addUpdateDock(1)"; class="btn btn-primary float-right" id="submit_button">
                            Adicionar Doca </button>

                        <button onclick="addUpdateDock(2)"; class="btn btn-primary float-right" id="update_button"
                            style="display:none;"> Atualizar Doca </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
