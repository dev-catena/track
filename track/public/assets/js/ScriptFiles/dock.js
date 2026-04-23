var DockId_Global = 0;
var MQTT_TopicId_Global = 0;
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var dockColumns = [
    { data: 'name', name: 'name' },
    { data: 'mac_address', name: 'mac_address' },
    { data: 'departmentName', name: 'departmentName' },
    { data: 'location', name: 'location' },
    { data: 'usage_capacity', name: 'usage_capacity' },
    { data: 'status', name: 'status' },
    { data: 'active_available_devices_count', name: 'active_available_devices_count' },
    { data: 'created_at', name: 'created_at' },
    { data: 'action', name: 'action' },
];
if (typeof showOrganizationColumn !== 'undefined' && showOrganizationColumn) {
    dockColumns.unshift({ data: 'organizationName', name: 'organizationName' });
}

var table = $('#dock-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: dockListUrl,
        data: function (d) {
            d.status = $('#statusFilter').val();
            if (typeof authrole !== 'undefined' && authrole === 'superadmin' && $('#global-org-select').length) {
                d.organization_id = $('#global-org-select').val();
            }
        },
    },
    columns: dockColumns,
    paging: true,
    searching: true,
    ordering: false,
    dom: 'tip',
    //pageLength: 9,
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
    const headers = ['Name', 'MAC', 'Department', 'Localização', 'Capacity','Status','Available Devices','Created On'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const name = escapeCSV(row.name) || '';
        const mac = escapeCSV(row.mac_address) || '';
        const location = escapeCSV(row.location) || '';
        const capacity = (row.devices_count || 0) + '/' + (row.capacity || 0);
        const status = row.status_2 || '';
        const availableDevices = row.active_available_devices_count_2 || 0;
        const createdOn = escapeCSV(row.created_at) || '';
        const department = escapeCSV(row.department.name) || '';
        csvContent += [name, mac, department, location, capacity, status, availableDevices, createdOn].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "dock_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
$('#statusFilter').on('change', function () {
    table.ajax.reload();
});

$(document).on('organization-changed', function (e, orgId) {
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
    fetchMqttTopics();
    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.form_data').show();
    if(mode == 1) {
        if (typeof syncFormOrganizationFromGlobalSelector === 'function') {
            syncFormOrganizationFromGlobalSelector();
        }
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add Dock');
        setTimeout(function() { $('#status').val('active').trigger('change'); }, 100);
    } else {
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update Dock');

    }
}

function formatDockNumberAsMac(dockNumber) {
    if (!dockNumber || typeof dockNumber !== 'string') return null;
    var clean = dockNumber.replace(/[:\-\s]/g, '').toLowerCase();
    if (clean.length !== 12 || !/^[0-9a-f]{12}$/.test(clean)) return null;
    return clean.match(/.{1,2}/g).join(':');
}

function resetForm() {
    $('#form')[0].reset();
    setTimeout(function() { $('#status').val('active').trigger('change'); }, 50);
    $('#description').text('');
    $('.error').text('');
    $('.heading_title').text('Docks Management');
    $('.form_data').hide();
    $('.data_list').show();
    DockId_Global = 0;
    $('#pairing-code-section').hide();
}


function addUpdateDock(mode) {
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
        url = dockSaveUrl;
    } else {

        url = dockUpdateUrl.replace(':id', DockId_Global);
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

    if ($('#organization_id').val().trim() === '') {
        $('#organization_error').text('Company is required.');
        isValid = false;
    }

    if ($('#name').val().trim() === '') {
        $('#name_error').text('Dock Name is required.');
        isValid = false;
    }
    if ($('#status').val().trim() === '') {
        $('#status_error').text('Status is required.');
        isValid = false;
    }

    if ($('#department_id').val().trim() === '') {
        $('#department_error').text('Department is required.');
        isValid = false;
    }

    if ($('#active_device').val().trim() === '') {
        $('#active_device_error').text('Active device is required.');
        isValid = false;
    }

    return isValid;
}



$('#btn-regenerate-pairing').on('click', function() {
    if (!DockId_Global) return;
    var url = dockRegeneratePairingUrl.replace(':id', DockId_Global);
    $.ajax({
        url: url,
        type: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            if (res.status == 1 && res.data && res.data.pairing_code) {
                $('#pairing-code-display').text(res.data.pairing_code);
                showAlert('success', res.message);
            }
        },
        error: function() { showAlert('error', 'Erro ao regenerar código.'); }
    });
});

function deleteDock(id) {
    var url = dockDeleteUrl.replace(':id', id);
    confirmAndDelete(url,table);
}

function getDockDetail(id) {
    var url = dockDetailUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var dock = response.data;
                $('#name').val(dock.name);
                $('#organization_id').val(dock.department.organization_id).trigger('change');
                setTimeout(function () {
                    $('#department_id').val(dock.department_id).trigger('change');
                }, 500);
                $('#status').val(dock.status).trigger('change');
                $('#capacity').val(dock.capacity);
                $('#location').val(dock.location);
                $('#dock_number').val(dock.dock_number);
                $('#description').text(dock.description);

                if (dock.pairing_code) {
                    $('#pairing-code-section').show();
                    $('#pairing-code-display').text(dock.pairing_code);
                    var macFormatted = formatDockNumberAsMac(dock.dock_number);
                    if (macFormatted) {
                        $('#mac-display').text(macFormatted);
                        $('#mac-display-row').show();
                    } else {
                        $('#mac-display-row').hide();
                    }
                } else {
                    $('#pairing-code-section').hide();
                }

                DockId_Global = dock.id;
                MQTT_TopicId_Global = dock.mqtt_topic_id;
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



function fetchMqttTopics() {

    var url = mqttTopicsUrl.replace(':id', DockId_Global);
    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var topics = response.data;

                let options = '<option value="">Select Active Device</option>';
                topics.forEach(function(topic) {
                    let selected = (topic.id == MQTT_TopicId_Global) ? 'selected' : '';
                    options += `<option value="${topic.id}" ${selected}>${topic.name}</option>`;
                });
                $('#active_device').html(options);
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

