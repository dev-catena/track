var DeviceId_Global = 0;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var table = $('#device-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url:deviceListUrl,
        data: function (d) {
            d.status = $('#statusFilter').val(),
            d.dock = $('#dockFilter').val()
        },
        // beforeSend: function () {
        //     StartLoading();
        // },
        // complete: function () {
        //     StopLoading();
        // }
    } ,
    columns: [
        { data: 'name', name: 'name' },
        { data: 'serial_number', name: 'serial_number' },
        { data: 'model_name', name: 'model_name' },
        { data: 'display_status', name: 'display_status' },
        { data: 'dockName', name: 'dockName' },
        { data: 'return_date', name: 'return_date' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action' }
    ],
    paging: true,
    searching: true,
    ordering: false,
    dom: 'tip',
    // pageLength: 9,
    drawCallback: function (settings) {
        let data = table.rows().data();
        let wrapper = $('#table-data');
        wrapper.empty();

        if (data.length === 0) {
            wrapper.append(`
                <div class="col-12">
                    <div class="alert text-center m-3">
                        No data available.
                    </div>
                </div>
            `);
            return;
        }
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
    const headers = ['Name', 'Build Number', 'Model', 'Status','Dock','Return Due','Created On'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const name = escapeCSV(row.name) || '';
        const buildNumber = escapeCSV(row.serial_number) || '';
        const model = escapeCSV(row.model_name) || '';
        const status = escapeCSV(row.device_status) || '';
        const dock = escapeCSV(row.dock.name + '(' + row.dock.location + ')') || '';
        const returnDue = escapeCSV(row.return_date) || '';
        const createdOn = escapeCSV(row.created_at) || '';
        csvContent += [name, buildNumber, model, status, dock, returnDue, createdOn].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "device_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

$('#statusFilter, #dockFilter').on('change', function () {
    table.ajax.reload();
});

let debounceSearchTimer;
$('#searchInput').on('input', function () {
    var searchTerm = $(this).val().trim();

    clearTimeout(debounceSearchTimer);

    debounceSearchTimer = setTimeout(function () {
        table.search(searchTerm).draw();
    }, 500);
});

function showModal(mode) {
    var modal = new bootstrap.Modal($('#smallModal')[0]);
    modal.show();
    setTimeout(function() {
        modal.hide();
        showForm(mode);
    }, 1000);
}
function showForm(mode) {

    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.form_data').show();
    if(mode == 1) {
        generateTag();
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add Device');
    } else {
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update Device');

    }
}

function resetForm() {
    $('#form')[0].reset();
    $('#description').text('');
    $('.error').text('');
    $('.heading_title').text('Device Management');
    $('.form_data').hide();
    $('.data_list').show();
    DeviceId_Global = 0;
}


function addUpdateDevice(mode) {
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
    formData.append('tag_id', $('#device_tag_data').text());
    if(mode == 1) {
        url = deviceSaveUrl;
    } else {

        url = deviceUpdateUrl.replace(':id', DeviceId_Global);
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

    if ($('#dock_id').val().trim() === '') {
        $('#dock_error').text('Dock is required.');
        isValid = false;
    }

    if ($('#name').val().trim() === '') {
        $('#name_error').text('Device Name is required.');
        isValid = false;
    }

    if ($('#model').val().trim() === '') {
        $('#model_error').text('Model is required.');
        isValid = false;
    }
    if ($('#status').val().trim() === '') {
        $('#status_error').text('Status is required.');
        isValid = false;
    }


    return isValid;
}



function deleteDevice(id) {
    var url = deviceDeleteUrl.replace(':id', id);
    confirmAndDelete(url,table);
}

function getDeviceDetail(id) {
    var url = deviceDetailUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var data = response.data;
                $('#name').val(data.name);
                $('#dock_id').val(data.dock_id).trigger('change');
                $('#model').val(data.model_name);
                $('#serial_number').val(data.serial_number);
                $('#status').val(data.status).trigger('change');
                $('#device_tag_data').text(data.tag_id);
                $('#description').text(data.description);

                DeviceId_Global = data.id;
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


function generateTag(len = 22) {
    let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let buf = new Uint8Array(len);
    window.crypto.getRandomValues(buf); // secure random
    let out = "";
    for (let i = 0; i < len; i++) {
        out += alphabet[buf[i] % alphabet.length];
    }
    $('#device_tag_data').text(out);
    //return out;
}
