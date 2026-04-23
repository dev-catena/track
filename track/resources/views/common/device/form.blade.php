<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <a class="cursor-pointer text-white btn btn-primary float-end"
                                        onclick="generateTag();">Ler Dispositivo Novamente</a>
                                </div>
                                <div class="col">
                                    <p>TAG: <span id="device_tag_data"></span></p>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dock_id">Doca <span class="required">*</label>
                                <select class="form-control select-control" id="dock_id" name="dock_id">
                                    <option value="">Selecionar Doca</option>
                                    @foreach ($docks as $dock)
                                        <option value="{{ $dock->id }}">{{ $dock->name }}</option>
                                    @endforeach

                                </select>
                                <span class="error" id="dock_error"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Nome do dispositivo">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Modelo <span class="required">*</span></label>
                                <input type="text" class="form-control" id="model" name="model_name"
                                    placeholder="Modelo do dispositivo">
                                <span class="error" id="model_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="serial_number">Número de Série</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number"
                                    placeholder="Número de série">
                                <span class="error" id="serial_number_error"></span>
                            </div>
                        </div>


                        <div class="col-md-6 ">
                            <label for="status">Status <span class="required">*</span></label>
                            <select class="form-control select-control" id="status" name="status">
                                <option value="">Selecionar Status</option>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="maintenance">Manutenção</option>
                            </select>
                            <span class="error" id="status_error"></span>

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
                        <button onclick="addUpdateDevice(1)"; class="btn btn-primary float-right" id="submit_button">
                            Adicionar Dispositivo </button>

                        <button onclick="addUpdateDevice(2)"; class="btn btn-primary float-right" id="update_button"
                            style="display:none;"> Atualizar Dispositivo </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
