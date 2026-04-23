<div class="card card-body rounded-3">
    <div class="row mb-3">
        <div class="col-md-9 my-2">
            <div class="position-relative">
                <input type="text" class="form-control" style="padding-left: 2rem;" placeholder="Buscar usuários..."
                    id="searchInput">
                <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
            </div>
        </div>
        <div class="col-md-3 my-2">
            <select class="form-control select-control" id="roleDropdown">
                <option value="">Filtrar por perfil (Todos)</option>
                @if ($user->role == 'superadmin')
                    <option value="admin">Admin</option>
                @endif
                <option value="manager">Gerente</option>
                <option value="operator">Operador</option>
            </select>
        </div>
    </div>
    <div class="table-responsive user-table-wrapper">
        <table id="user-table" class="table table-hover table-rounded">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Perfil</th>
                    <th>Criado em</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
