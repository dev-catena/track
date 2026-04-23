<div class="row my-3">
    <div class="col-md-12">
        <div class="position-relative">
            <input type="text" class="form-control" style="padding-left: 2rem;" placeholder="Buscar departamentos..."
                id="searchInput">
            <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
        </div>
    </div>
    {{-- Empresa: usa o seletor global na sidebar (#global-org-select) --}}
</div>




<div class="table-responsive dept-table-wrapper">
    <table class="table table-hover table-striped" id="dept-display-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Localização</th>
                <th>Departamento pai</th>
                <th>Docas</th>
                <th>Dispositivos</th>
                <th style="width: 80px;">Ações</th>
            </tr>
        </thead>
        <tbody id="table-data">
        </tbody>
    </table>
</div>

<table id="dept-table" class="d-none">
    <thead>
        <tr>
            <th>Details</th>
        </tr>
    </thead>
</table>
