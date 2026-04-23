<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Ex: Administrador">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        <input type="hidden" id="code" name="code" value="">
                        <input type="hidden" id="assignable_by" name="assignable_by" value="">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sort_order">Ordem</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="requires_username" name="requires_username" value="1">
                                <label class="form-check-label" for="requires_username">Requer nome de usuário</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="is_operator" name="is_operator" value="1">
                                <label class="form-check-label" for="is_operator">É operador (tabela operators)</label>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button onclick="addUpdateProfile(1)" class="btn btn-primary float-right" id="submit_button">Adicionar Perfil</button>
                        <button onclick="addUpdateProfile(2)" class="btn btn-primary float-right" id="update_button" style="display:none;">Atualizar Perfil</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
