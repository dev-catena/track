var DepartmentId_Global = 0;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var table = $('#dept-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url:departmentListUrl,
        data: function (d) {
            if(role == 'admin') {
                d.organizationId = $('#organization_id').val();
            } else {
                d.organizationId = $('#global-org-select').val();
            }
            d.searchTerm = $('#searchInput').val();
        },
        // beforeSend: function () {
        //     StartLoading();
        // },
        // complete: function () {
        //     StopLoading();
        // }
    } ,
    columns: [
        { data: 'details', name: 'details' }
    ],
    paging: true,
    searching: true,
    ordering: false,
    dom: 'ip',
    pageLength: 9,
    drawCallback: function (settings) {
        let data = table.rows().data();
        let wrapper = $('#table-data');
        wrapper.empty();

        if (data.length === 0) {
            wrapper.html('<tr><td colspan="6" class="text-center py-4 text-muted">Nenhum departamento encontrado.</td></tr>');
            return;
        }

        wrapper.empty();
        data.each(function (row) {
            wrapper.append(row.details);
        });
    }
});


$('#exportButtonCSV').on('click', function () {
    const data = table.rows().data();
    if (!data.length) {
        showAlert('warning', 'No data available to export.');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";

    // Define CSV header
    const headers = ['Name', 'Localização', 'Description','Docks', 'Devices'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const name = escapeCSV(row.name) || '';
        const location = escapeCSV(row.location) || '';
        const description = escapeCSV(row.description) || '';
        const devicesCount = row.device_count || 0;
        const docksCount = row.dock_count || 0;

        csvContent += [name, location, description, docksCount, devicesCount].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "department_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

$('#global-org-select').on('change', function () {
    table.ajax.reload();
});
$(document).on('organization-changed', function(e, orgId) {
    if (typeof table !== 'undefined') table.ajax.reload();
});


let debounceSearchTimer;
$('#searchInput').on('input', function () {
    var searchTerm = $(this).val().trim();

    clearTimeout(debounceSearchTimer);

    debounceSearchTimer = setTimeout(function () {
        table.search(searchTerm).draw();
    }, 500);
});

function showForm(mode) {
    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.form_data').show();
    if(mode == 1) {
        if (typeof syncFormOrganizationFromGlobalSelector === 'function') {
            syncFormOrganizationFromGlobalSelector();
        }
        $('#status').val('active').trigger('change');
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add Department');
    } else {
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update Department');

    }
}

function resetForm() {
    $('#form')[0].reset();
    $('#status').val('active').trigger('change');
    $('#description').text('');
    if ($('#parent_id').length) $('#parent_id option').prop('disabled', false);
    $('.error').text('');
    $('.heading_title').text('Departments');
    $('.form_data').hide();
    $('.data_list').show();
    DepartmentId_Global = 0;
}


function addUpdateDepartment(mode) {
    $('.error').text(''); // Clear previous errors
    $('#submit_button').prop('disabled', true);
    $('#update_button').prop('disabled', true);

    // Validate form fields
    var isValid = validateForm();

    var formData = new FormData($('#form')[0]);
    if (!isValid) {
        $('#submit_button').prop('disabled', false);
        $('#update_button').prop('disabled', false);
        return;
    }

    if(mode == 1) {
        url = departmentSaveUrl;
    } else {

        url = departmentUpdateUrl.replace(':id', DepartmentId_Global);
        formData.append('_method', 'PUT');
    }

    StartLoading();
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            StopLoading();
            if (response.status == 1) {

                showAlert('success', response.message);
                table.ajax.reload();
                resetForm();
            } else {
                // Handle validation errors
                showAlert('error', response.message);
            }
            $('#submit_button').prop('disabled', false);
            $('#update_button').prop('disabled', false);
        },
        error: function(xhr) {
            StopLoading();
            showAlert('error', 'Internal server error!');
            $('#submit_button').prop('disabled', false);
            $('#update_button').prop('disabled', false);

            //console.log(xhr.responseText);
        }
    });
}

function validateForm() {
    var isValid = true;

    // Check if name is empty
    if ($('#name').val().trim() === '') {
        $('#name_error').text('Department Name is required.');
        isValid = false;
    }

    if ($('#organization_id').val().trim() === '') {
        $('#organization_error').text('Organization is required.');
        isValid = false;
    }

    $('#operating_start_error').text('');
    $('#operating_end_error').text('');

    var start = $('#operating_start').val().trim();
    var end = $('#operating_end').val().trim();

    // Mutual dependency validation
    if (start !== '' && end === '') {
        $('#operating_error').text('Operating End Time is required.');
        isValid = false;
    }

    if (end !== '' && start === '') {
        $('#operating_error').text('Operating Start Time is required.');
        isValid = false;
    }

    // Compare time values only if both are filled
    if (start !== '' && end !== '') {
        var startTime = new Date(`1970-01-01T${start}`);
        var endTime = new Date(`1970-01-01T${end}`);

        if (startTime >= endTime) {
            $('#operating_error').text('Start Time must be earlier than End Time.');
            isValid = false;
        }
    }
    if ($('#status').val().trim() === '') {
        $('#status_error').text('Status is required.');
        isValid = false;
    }

    return isValid;
}



function deleteDepartment(id) {
    var url = departmentDeleteUrl.replace(':id', id);
    confirmAndDelete(url,table);
}

function getDepartmentDetail(id) {
    var url = departmentDetailUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var dept = response.data;
                $('#name').val(dept.name);
                $('#organization_id').val(dept.organization_id).trigger('change');
                if ($('#organization_display').length) {
                    $('#organization_display').val(dept.organization?.name || dept.organization_name || '');
                }
                if ($('#parent_id').length) {
                    $('#parent_id option').prop('disabled', false);
                    $('#parent_id option[value="' + dept.id + '"]').prop('disabled', true);
                    $('#parent_id').val(dept.parent_id || '').trigger('change');
                }
                $('#status').val(dept.status).trigger('change');
                $('#internal_id').val(dept.internal_id);
                $('#location').val(dept.location);
                $('#operating_start').val(dept.operating_start);
                $('#operating_end').val(dept.operating_end);
                $('#description').text(dept.description);

                DepartmentId_Global = dept.id;
                showForm(2)
            } else {
                showAlert('warning', response.message || 'Something went wrong.');
            }
        },
        error: function () {
            StopLoading();
            showAlert('error', 'Internal server error!');
        }
    });
}
