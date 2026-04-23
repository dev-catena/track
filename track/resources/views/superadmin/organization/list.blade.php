<div class="row mb-2">
    <div class="col-12 ">
        <button class="btn btn-primary float-right ml-3" id="exportButtonCSV">
            <i class="fa fa-download"></i>&nbsp; Exportar</button>
        {{-- <button class="btn btn-primary float-right ml-3" id="exportButtonPDF">
            <i class="fa fa-download"></i>&nbsp; Export as PDF</button> --}}
        <button class="btn btn-primary float-right " onclick="showForm(1);">
            <i class="fa fa-plus"></i>&nbsp; Adicionar Empresa</button>
    </div>
</div>
<div class="row my-3">
    <div class="col-md-9 my-2">
        <div class="position-relative">
            <input type="text" class="form-control" style="padding-left: 2rem;" placeholder="Buscar empresas..."
                id="searchInput">
            <i class="fa fa-search" style="position: absolute; left: 10px; top: 10px;"></i>
        </div>
    </div>
    <div class="col-md-3 my-2">
        <select class="form-control select-control" id="planDropdown">
            <option value="">Filtrar por Plano (Todos)</option>
            @foreach ($plans as $plan_id => $plan_name)
                <option value="{{ $plan_id }}">{{ $plan_name }}</option>
            @endforeach
        </select>
    </div>
</div>




<div class="row " id="table-data">

</div>

<table id="org-table" class="d-none">
    <thead>
        <tr>
            <th>Detalhes</th>
        </tr>
    </thead>
</table>
