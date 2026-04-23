$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

var table = $('#profile-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: profileListUrl },
    columns: [{ data: 'details', name: 'details' }],
    paging: true,
    searching: true,
    ordering: false,
    dom: 'ip',
    pageLength: 9,
    drawCallback: function (settings) {
        var data = table.rows().data();
        var wrapper = $('#table-data');
        wrapper.empty();
        if (data.length === 0) {
            wrapper.append('<div class="col-12"><div class="alert text-center m-3">Nenhum perfil cadastrado.</div></div>');
            return;
        }
        data.each(function (row) { wrapper.append(row.details); });
    }
});

$('#searchInput').on('input', function () {
    var v = $(this).val().trim();
    clearTimeout(window._profileSearch);
    window._profileSearch = setTimeout(function () { table.search(v).draw(); }, 500);
});
