/* global $, dockReportDataUrl, dockReportExportUrl, ajaxListsUrl, csrfToken */

function initDockTable() {
  if ($.fn.DataTable.isDataTable('#dock-history-table')) {
    $('#dock-history-table').DataTable().destroy();
  }

  $('#dock-history-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: dockReportDataUrl,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      data: function (d) {
        d.dock_id = $('#dock_id').val();
        d.date_from = $('#date_from').val();
        d.date_to = $('#date_to').val();
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || xhr.statusText || 'Erro ao carregar';
        if (typeof showAlert === 'function') showAlert('error', msg);
        else alert(msg);
      },
    },
    columns: [
      { data: 'tipo', name: 'tipo', orderable: false },
      { data: 'operador', name: 'operador', orderable: false },
      { data: 'email_operador', name: 'email_operador', orderable: false },
      { data: 'dispositivo', name: 'dispositivo', orderable: false },
      { data: 'quando', name: 'quando', orderable: false },
    ],
    paging: true,
    searching: false,
    ordering: false,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
    },
  });
}

$('#btnApplyDockReport').on('click', function () {
  if (!$('#dock_id').val()) {
    if (typeof showAlert === 'function') showAlert('warning', 'Selecione uma doca.');
    else alert('Selecione uma doca.');
    return;
  }
  initDockTable();
});

$('#exportDockCsv').on('click', function () {
  if (!$('#dock_id').val()) {
    if (typeof showAlert === 'function') showAlert('warning', 'Selecione uma doca.');
    else alert('Selecione uma doca.');
    return;
  }
  var q = $.param({
    dock_id: $('#dock_id').val(),
    date_from: $('#date_from').val(),
    date_to: $('#date_to').val(),
  });
  window.location.href = dockReportExportUrl + '?' + q;
});

if (typeof ajaxListsUrl === 'string' && ajaxListsUrl) {
  $('#organization_id').on('change', function () {
    var oid = $(this).val();
    if (!oid) return;
    $.ajax({
      url: ajaxListsUrl,
      method: 'POST',
      data: { organization_id: oid, _token: csrfToken },
      success: function (res) {
        if (!res.success) return;
        var $d = $('#dock_id').empty().append('<option value="">Selecione...</option>');
        (res.docks || []).forEach(function (d) {
          $d.append($('<option></option>').attr('value', d.id).text(d.name));
        });
      },
    });
  });
}
