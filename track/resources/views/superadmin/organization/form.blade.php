<div class="row mb-2">
    <div class="col-12 ">
        <button class="btn btn-primary float-right" onclick="resetForm();">
            <i class="fa fa-arrow-left"></i>&nbsp; Voltar</button>
    </div>
</div>
<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome da Empresa <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Nome da Empresa">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cnpj">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj"
                                    placeholder="00.000.000/0000-00">
                                <span class="error" id="cnpj_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">E-mail <span class="required">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="E-mail de contato">
                                <span class="error" id="email_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Telefone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    placeholder="(11) 99999-9999">
                                <span class="error" id="phone_error"></span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea rows="3" class="form-control" id="address" name="address" autocomplete="off" placeholder="Endereço"></textarea>
                                <span class="error" id="address_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">Cidade</label>
                                <input type="text" class="form-control" id="city" name="city"
                                    placeholder="Cidade">
                                <span class="error" id="city_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">Estado</label>
                                <input type="text" class="form-control" id="state" name="state"
                                    placeholder="Estado">
                                <span class="error" id="state_error"></span>
                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_devices">Máx. Dispositivos</label>
                                <input type="text" class="form-control" id="max_devices" name="max_devices"
                                    min="1" placeholder="Máx. Dispositivos">
                                <span class="error" id="max_devices_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="plan_id">Plano</label>
                                <select class="form-control select-control" id="plan_id" name="plan_id">
                                    <option value="">Selecionar Plano</option>
                                    @foreach ($plans as $plan_id => $plan_name)
                                        <option value="{{ $plan_id }}">{{ $plan_name }}</option>
                                    @endforeach

                                </select>
                                <span class="error" id="plan_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mdm">MDM</label>
                                <select class="form-control select-control" id="mdm" name="mdm">
                                    <option value="">Selecionar MDM</option>
                                    <option value="Samsung Knox">Samsung Knox</option>
                                    <option value="Microsoft Intune">Microsoft Intune</option>
                                    <option value="Pulsus">Pulsus</option>
                                </select>
                                <span class="error" id="mdm_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select class="form-control select-control" id="status" name="status">
                                    <option value="">Selecionar Status</option>
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                                <span class="error" id="status_error"></span>
                            </div>
                        </div>
                    </div>

                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="addUpdateCompany(1)"; class="btn btn-primary float-right"
                            id="submit_button"> Adicionar Empresa </button>

                        <button onclick="addUpdateCompany(2)"; class="btn btn-primary float-right" id="update_button"
                            style="display:none;"> Atualizar Empresa </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
