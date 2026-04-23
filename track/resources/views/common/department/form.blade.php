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
                                    <label for="organization_id">Empresa <span class="required">*</span></label>
                                    @if ($selectedOrganizationId && $selectedOrganizationName)
                                        <input type="text" class="form-control" id="organization_display" value="{{ $selectedOrganizationName }}" readonly>
                                        <input type="hidden" name="organization_id" id="organization_id" value="{{ $selectedOrganizationId }}">
                                        <small class="form-text text-muted">Altere a empresa no seletor acima do menu.</small>
                                    @else
                                        <select class="form-control select-control" id="organization_id" name="organization_id">
                                            <option value="">Selecionar Empresa</option>
                                            @foreach ($organizations as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <span class="error" id="organization_error"></span>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="organization_id" id="organization_id"
                                value="{{ $organizations }}">
                            <span class="error" id="organization_error"></span>
                        @endif

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Nome do departamento">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        @if (isset($parentDepartments) && $parentDepartments->isNotEmpty())
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="parent_id">Departamento pai</label>
                                    <select class="form-control select-control" id="parent_id" name="parent_id">
                                        <option value="">Nenhum (departamento raiz)</option>
                                        @foreach ($parentDepartments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Opcional. Deixe em branco para departamento de nível raiz.</small>
                                    <span class="error" id="parent_error"></span>
                                </div>
                            </div>
                        @endif
                        <input type="hidden" id="internal_id" name="internal_id" value="">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location">Localização</label>
                                <input type="text" class="form-control" id="location" name="location"
                                    placeholder="Localização">
                                <span class="error" id="location_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <label for="operating_hours">Horário Padrão de Operação</label>
                            <div class="row">
                                <div class="col-3">
                                    <div class="form-group">
                                        <input type="time" class="form-control" id="operating_start"
                                            name="operating_start">

                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">

                                        <input type="time" class="form-control" id="operating_end"
                                            name="operating_end">

                                    </div>
                                </div>

                            </div>
                            <span class="error" id="operating_error"></span>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select class="form-control select-control" id="status" name="status">
                                    <option value="active" selected>Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                                <span class="error" id="status_error"></span>
                            </div>
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
                        <button onclick="addUpdateDepartment(1)"; class="btn btn-primary float-right"
                            id="submit_button">
                            Adicionar Departamento </button>

                        <button onclick="addUpdateDepartment(2)"; class="btn btn-primary float-right"
                            id="update_button" style="display:none;"> Atualizar Departamento </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
