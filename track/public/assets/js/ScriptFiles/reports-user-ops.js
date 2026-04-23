/* global $, userOpsDataUrl, userOpsExportUrl, ajaxListsUrl, csrfToken, REPORT_OPERATORS, REPORT_USERS, isSuperAdmin */

function fillSubjectSelect(mode) {
  var $s = $('#subject_id').empty().append('<option value="">Selecione...</option>');
  var list = mode === 'operator' ? REPORT_OPERATORS : REPORT_USERS;
  (list || []).forEach(function (row) {
    var label = row.name + ' (' + (row.email || '') + ')';
    if (mode === 'user' && row.role) {
      label = row.name + ' — ' + row.role + ' (' + (row.email || '') + ')';
    }
    $s.append($('<option></option>').attr('value', row.id).text(label));
  });
}

function toggleTables(mode) {
  if (mode === 'operator') {
    $('#wrap-table-operator').show();
    $('#wrap-table-user').hide();
  } else {
    $('#wrap-table-operator').hide();
    $('#wrap-table-user').show();
  }
}

function initOperatorTable() {
  if ($.fn.DataTable.isDataTable('#user-ops-operator-table')) {
    $('#user-ops-operator-table').DataTable().destroy();
  }
  $('#user-ops-operator-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: userOpsDataUrl,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      data: function (d) {
        d.mode = 'operator';
        d.subject_id = $('#subject_id').val();
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
      { data: 'origem', orderable: false },
      { data: 'tipo', orderable: false },
      { data: 'doca', orderable: false },
      { data: 'dispositivo', orderable: false },
      { data: 'quando', orderable: false },
    ],
    searching: false,
    ordering: false,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
    },
  });
}

function initUserTable() {
  if ($.fn.DataTable.isDataTable('#user-ops-user-table')) {
    $('#user-ops-user-table').DataTable().destroy();
  }
  $('#user-ops-user-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: userOpsDataUrl,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      data: function (d) {
        d.mode = 'user';
        d.subject_id = $('#subject_id').val();
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
      { data: 'origem', orderable: false },
      { data: 'tipo', orderable: false },
      { data: 'entidade', orderable: false },
      { data: 'detalhe', orderable: false },
      { data: 'ip', orderable: false },
      { data: 'quando', orderable: false },
    ],
    searching: false,
    ordering: false,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
    },
  });
}

$('#mode').on('change', function () {
  fillSubjectSelect($(this).val());
  toggleTables($(this).val());
});

$('#btnApplyUserReport').on('click', function () {
  if (!$('#subject_id').val()) {
    if (typeof showAlert === 'function') showAlert('warning', 'Selecione uma pessoa.');
    else alert('Selecione uma pessoa.');
    return;
  }
  var mode = $('#mode').val();
  if (mode === 'operator') {
    initOperatorTable();
  } else {
    initUserTable();
  }
});

$('#exportUserCsv').on('click', function () {
  if (!$('#subject_id').val()) {
    if (typeof showAlert === 'function') showAlert('warning', 'Selecione uma pessoa.');
    else alert('Selecione uma pessoa.');
    return;
  }
  var q = $.param({
    mode: $('#mode').val(),
    subject_id: $('#subject_id').val(),
    date_from: $('#date_from').val(),
    date_to: $('#date_to').val(),
  });
  window.location.href = userOpsExportUrl + '?' + q;
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
        window.REPORT_OPERATORS = res.operators || [];
        window.REPORT_USERS = res.users || [];
        fillSubjectSelect($('#mode').val());
      },
    });
  });
}

$(function () {
  fillSubjectSelect($('#mode').val());
  toggleTables($('#mode').val());
});
