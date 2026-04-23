var OgranizationId_Global = 0;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var table = $('#org-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url:organizationListUrl,
        data: function (d) {
            // d.searchTerm = $('#searchInput').val();
            d.planId = $('#planDropdown').val();
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
            wrapper.append(`
                <div class="col-12">
                    <div class="alert text-center m-3">
                        No data available.
                    </div>
                </div>
            `);
            return;
        }

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
    const headers = ['Name', 'Email', 'Phone', 'Plan','Address','Users','Devices','Docks','Max Users','Max Devices', 'Created At'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const name = row.name || '';
        const email = row.email || '';
        const plan = row.plan.name || '';
        const phone = row.phone || '';
        const address = escapeCSV(row.address) || '';
        const usersCount = (row.user_count || 0) + (row.operator_count || 0);
        const devicesCount = row.device_count || 0;
        const docksCount = row.dock_count || 0;

        const maxUsers = row.max_users || 0;
        const maxDevices = row.max_devices || 0;
        const createdAt = escapeCSV(row.created_at) || '';

        csvContent += [name, email, phone, plan, address, usersCount, devicesCount, docksCount, maxUsers, maxDevices, createdAt].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "organization_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

// $('#exportButtonPDF').on('click', function () {
//     const contentWrapper = document.createElement('div');

//     // Extract data from DataTable rows (already rendered as HTML)
//     table.rows().data().each(function (row) {
//         const tempDiv = document.createElement('div');
//         tempDiv.innerHTML = row.details;

//         // Optional: Clean up or add styles for PDF layout
//         tempDiv.querySelector('.dropdown')?.remove(); // remove dropdown from export

//         contentWrapper.appendChild(tempDiv);
//     });

//     if (!contentWrapper.innerHTML.trim()) {
//         alert('No data to export');
//         return;
//     }

//     // Convert to PDF
//     const opt = {
//         margin:       0.5,
//         filename:     'organizations.pdf',
//         image:        { type: 'jpeg', quality: 0.98 },
//         html2canvas:  { scale: 2 },
//         jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
//     };

//     html2pdf().set(opt).from(contentWrapper).save();
// });

$('#planDropdown').on('change', function () {
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

function showForm(mode) {
    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.form_data').show();
    if(mode == 1) {
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add Company');
    } else {
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update Company');

    }
}

function resetForm() {
    $('#form')[0].reset();
    $('#address').text('');
    $('.error').text('');
    $('.heading_title').text('Companies');
    $('.form_data').hide();
    $('.data_list').show();
    OgranizationId_Global = 0;
}

function addUpdateCompany(mode) {
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
        url = organizationSaveUrl;
    } else {

        url = organizationUpdateUrl.replace(':id', OgranizationId_Global);
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
        $('#name_error').text('Company Name is required.');
        isValid = false;
    }

    // Check if email is valid
    var email = $('#email').val().trim();
    if (email === '') {
        $('#email_error').text('Email is required.');
        isValid = false;
    } else if (email !== '' && !validateEmail(email)) {
        $('#email_error').text('Invalid email format.');
        isValid = false;
    }

    var cnpj = $('#cnpj').val().replace(/\D/g, '');

    if (cnpj !== '' && cnpj.length !== 14) {
        $('#cnpj_error').text('Please enter a valid CNPJ with exactly 14 digits.');
        isValid = false;
    }
    // Check if phone number is valid (validate raw digits: 10 or 11 for BR)
    var phoneRaw = $('#phone').val().replace(/\D/g, '');
    if (phoneRaw !== '' && (phoneRaw.length < 10 || phoneRaw.length > 11)) {
        $('#phone_error').text('Informe um telefone válido (10 ou 11 dígitos).');
        isValid = false;
    }
    if ($('#status').val().trim() === '') {
        $('#status_error').text('Status is required.');
        isValid = false;
    }
    return isValid;
}

function deleteOrganization(id) {
    var url = organizationDeleteUrl.replace(':id', id);
    confirmAndDelete(url,table);
}

function getOrganizationDetail(id) {
    var url = organizationDetailUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var org = response.data;
                $('#name').val(org.name);
                $('#email').val(org.email);
                $('#cnpj').val(org.cnpj);
                $('#phone').val(org.phone ? formatPhone(org.phone) : '');
                $('#address').text(org.address);
                $('#city').val(org.city);
                $('#state').val(org.state);
                $('#max_devices').val(org.max_devices);
                $('#plan_id').val(org.plan?.id).trigger('change');
                $('#mdm').val(org.mdm).trigger('change');
                $('#status').val(org.status).trigger('change');
                OgranizationId_Global = org.id;
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


$('#cnpj').on('input', function() {
    $(this).val(formatCNPJ($(this).val()));
});

$('#phone').on('input', function() {
    $(this).val(formatPhone($(this).val()));
});

$('#cnpj').on('blur', function() {
    let raw = $(this).val().replace(/\D/g, '');
    if (raw.length !== 14) {
        $('#cnpj_error').text('CNPJ must be exactly 14 digits (format: 00.000.000/0000-00)');
        $(this).focus();
    } else {
        $('#cnpj_error').text('');
    }
});

function formatCNPJ(val) {
    val = val.replace(/\D/g, ''); // remove non-digits
    if (val.length > 14) val = val.substr(0, 14);

    let formatted = val;
    if (val.length > 2) formatted = val.substr(0, 2) + '.' + val.substr(2);
    if (val.length > 5) formatted = formatted.substr(0, 6) + '.' + formatted.substr(6);
    if (val.length > 8) formatted = formatted.substr(0, 10) + '/' + formatted.substr(10);
    if (val.length > 12) formatted = formatted.substr(0, 15) + '-' + formatted.substr(15);

    if (val.length == 14) {
        $('#cnpj_error').text('');
    }
    return formatted;
}

function formatPhone(val) {
    val = val.replace(/\D/g, '');
    if (val.length > 11) val = val.substr(0, 11);
    if (val.length === 0) return '';
    if (val.length <= 2) return val.length ? '(' + val : '';
    if (val.length <= 6) return '(' + val.substr(0, 2) + ') ' + val.substr(2);
    if (val.length <= 10) return '(' + val.substr(0, 2) + ') ' + val.substr(2, 4) + '-' + val.substr(6);
    return '(' + val.substr(0, 2) + ') ' + val.substr(2, 5) + '-' + val.substr(7);
}
