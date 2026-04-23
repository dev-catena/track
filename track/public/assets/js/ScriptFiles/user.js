var UserId_Global = 0;
var UserRole_Global = '';
var ROLE_NAMES = { operator: 'Operador', admin: 'Administrador', manager: 'Gerente', superadmin: 'Super Administrador' };
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

var table = $('#user-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: userListUrl,
        data: function (d) {
            d.role = $('#roleDropdown').val();
            if (authrole === 'superadmin' && $('#global-org-select').length) {
                d.organization_id = $('#global-org-select').val();
            }
        }
    },
    columns: [
        { data: 'name_column', name: 'name', orderable: false },
        { data: 'email', name: 'email', orderable: false },
        { data: 'phone', name: 'phone', orderable: false },
        { data: 'status_column', name: 'status', orderable: false },
        { data: 'role_column', name: 'role', orderable: false },
        { data: 'created_at', name: 'created_at', orderable: false },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    ],
    paging: true,
    searching: true,
    ordering: false,
    dom: 'tip',
    pageLength: 10,
    language: {
        emptyTable: 'Nenhum usuário encontrado.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_',
        infoEmpty: 'Nenhum registro',
        lengthMenu: 'Mostrar _INPUT_ por página',
        search: 'Buscar:',
        zeroRecords: 'Nenhum resultado encontrado'
    }
});

$('#roleDropdown').on('change', function () {
    table.ajax.reload();
});

$('#global-org-select').on('change', function () {
    if (typeof table !== 'undefined') table.ajax.reload();
});
$(document).on('organization-changed', function (e, orgId) {
    if (typeof table !== 'undefined') table.ajax.reload();
});

$('#avatar').on('change', function () {
    var file = this.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#avatar_preview_img').attr('src', e.target.result);
            $('#avatar_preview').show();
        };
        reader.readAsDataURL(file);
    } else {
        $('#avatar_preview').hide();
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
    const headers = ['Name', 'Email', 'Status', 'Role','Phone','Created On'];
    csvContent += headers.join(",") + "\r\n";

    // Extract values from the `row.details` HTML (you can adjust this structure)
    data.each(function (row) {


        const name = escapeCSV(row.name) || '';
        const email = escapeCSV(row.email) || '';
        const status = escapeCSV(row.status) || '';
        const role = escapeCSV(row.role) || '';
        const phone = escapeCSV(row.phone) || '';
        const createdOn = escapeCSV(row.created_at) || '';
        csvContent += [name, email, status, role, phone, createdOn].join(",") + "\r\n";
    });

    // Create and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "user_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
    if (mode == 1) {
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add User');
        $('#avatar_preview').hide();
        $('#avatar_preview_img').attr('src', '');
        $('#avatar').val('');
        $('.password_div').show();
        $('#role').show().prop('disabled', false).removeClass('bg-light');
        $('#role_display').hide();
        if (typeof syncFormOrganizationFromGlobalSelector === 'function') {
            syncFormOrganizationFromGlobalSelector();
        }
        var orgId = $('#organization_id').val();
        if (orgId && typeof getDepartmentListByCompanyId === 'function') {
            getDepartmentListByCompanyId(orgId);
        }
    } else {
        $('.password_div').hide();
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update User');

    }
}

function ensureRoleOption(code) {
    if (!$('#role option[value="' + code + '"]').length) {
        $('#role').append($('<option></option>').attr('value', code).text(ROLE_NAMES[code] || code));
    }
}

function resetForm() {
    $('#form')[0].reset();
    $('#role').prop('disabled', false).show().removeClass('bg-light');
    $('#role_display').hide();
    $('#avatar_preview').hide();
    $('#avatar_preview_img').attr('src', '');
    $('.error').text('');
    $('.heading_title').text('Users');
    $('.form_data').hide();
    $('.data_list').show();
    UserId_Global = 0;
    UserRole_Global = 0;
}


function addUpdateUser(mode) {
    $('.error').text(''); // Clear previous errors
    $('#submit_button').prop('disabled', true);
    $('#update_button').prop('disabled', true);

    // Validate form fields
    var isValid = validateForm();
    var role = $('#role').val();
    var formData = new FormData($('#form')[0]);
    if (!isValid) {
        $('#submit_button').prop('disabled', false);
        $('#update_button').prop('disabled', false);
        return;
    }
    var url = userSaveUrl;
    if (mode == 1) {
        url = userSaveUrl;
    } else {
        role = UserRole_Global;
        url = role == 'operator' ? operatorUpdateUrl : userUpdateUrl;
        url = url.replace(':id', UserId_Global);
        formData.append('_method', 'PUT');
        // Garantir que o nome seja enviado (campo pode ficar fora do FormData em alguns cenários)
        formData.set('name', $('#name').val() || '');
    }

    StartLoading();
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
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
        error: function (xhr) {
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

    // Check if name is empty (val() pode retornar null em selects)
    if (($('#name').val() || '').trim() === '') {
        $('#name_error').text('User Name is required.');
        isValid = false;
    }

    if (($('#organization_id').val() || '').trim() === '') {
        $('#organization_error').text('Organization is required.');
        isValid = false;
    }

    var crnt_user_role = ($('#role').val() || '').trim();
    if (crnt_user_role === '') {
        $('#role_error').text('Role is required.');
        isValid = false;
    }
    if (crnt_user_role !== 'admin') {
        if (($('#department_id').val() || '').trim() === '') {
            $('#department_error').text('Department is required.');
            isValid = false;
        }
    }

    // Check if email is valid
    var email = ($('#email').val() || '').trim();
    if (email === '') {
        $('#email_error').text('Email is required.');
        isValid = false;
    } else if (email !== '' && !validateEmail(email)) {
        $('#email_error').text('Invalid email format.');
        isValid = false;
    }

    // Check if phone number is valid
    var phone = ($('#phone').val() || '').trim();
    if (phone !== '' && !validatePhone(phone)) {
        $('#phone_error').text('Invalid phone number format.');
        isValid = false;
    }

    if (($('#status').val() || '').trim() === '') {
        $('#status_error').text('Status is required.');
        isValid = false;
    }



    return isValid;
}



function deleteUser(id, role) {
    var url = role == 'operator' ? operatorDeleteUrl : userDeleteUrl;
    url = url.replace(':id', id);
    confirmAndDelete(url, table);
}

function getUserDetail(id, role) {
    var url = role == 'operator' ? operatorDetailUrl : userDetailUrl;
    url = url.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var user = response.data;
                $('#name').val(user.name);
                $('#email').val(user.email);
                if (authrole == 'superadmin') {
                    $('#organization_id').val(user.organization_id).trigger('change');
                } else if (authrole == 'admin' || authrole == 'manager') {
                    if (typeof getDepartmentListByCompanyId === 'function') {
                        getDepartmentListByCompanyId(user.organization_id);
                    }
                }
                setTimeout(function () {
                    $('#department_id').val(String(user.department_id)).trigger('change');
                }, 800);
                $('#phone').val(user.phone);
                $('#operation').val(user.operation || 'indoor');
                ensureRoleOption(user.role);
                $('#role').val(user.role).trigger('change');
                $('#role_display').text(ROLE_NAMES[user.role] || $('#role option:selected').text() || user.role).show();
                $('#role').hide();
                $('#username').val(user.username);
                $('#status').val(user.status).trigger('change');

                if (user.avatar_url) {
                    $('#avatar_preview_img').attr('src', user.avatar_url);
                    $('#avatar_preview').show();
                } else {
                    $('#avatar_preview').hide();
                }
                $('#avatar').val('');

                UserId_Global = user.id;
                UserRole_Global = user.role;
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


function showQr(userId) {
    var svgHtml = document.getElementById('qr-' + userId).innerHTML;
    document.getElementById('qrCodeContainer').innerHTML = svgHtml;

    var modal = new bootstrap.Modal(document.getElementById('qrModal'));
    modal.show();
}
function registerFaceModal(userId,faceId = '') {
    UserId_Global = userId;
    // Rota operator/{id}/face-detail usa o ID numérico do operador, não o face_id da Thalamus
    var url = operatorFaceDetailUrl.replace(':id', userId);
    var myModalEl = document.getElementById('registerFaceModal');
    $('#face_image_show').hide();
    if(faceId != '') {
        StartLoading();
        $.ajax({
            url: url,
            type: 'GET',
            contentType: false,
            processData: false,
            success: function (response) {
                StopLoading();
                if (response.status == 1) {

                    if (response.data.face_image && response.data.face_image != 'face_image') {
                        $('#face_image_show')
                        .attr('src', response.data.face_image)
                        .css({
                            'width': '200px',
                            'height': '200px',
                            'object-fit': 'cover',
                            'border-radius': '8px',
                            'border': '1px solid #ddd',
                            'display': 'block',
                            'margin': 'auto'
                        })
                        .show();
                    }


                } else if(response.status == -1) {
                    showAlert('error', response.data?.message);
                }

                // Create modal instance
                var modal = new bootstrap.Modal(myModalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            },
            error: function (xhr) {
                StopLoading();
                showAlert('error', 'Internal server error!');

            }
        });
    } else {
        // Create modal instance
        var modal = new bootstrap.Modal(myModalEl, {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();
    }



}


function toggleUsername(value) {
    var sel = $('#role option:selected');
    var requires = sel.data('requires-username');
    if (value && requires == 1) {
        $('.username_div').show();
    } else {
        $('.username_div').hide();
    }
}


function registerUserFace() {
    $('#image_error').text('');
    $('#submitFace_button').prop('disabled', true);

    // Check if file selected
    var fileInput = $('#image')[0];
    if (fileInput.files.length === 0) {
        $('#image_error').text('Please select an image');
        $('#submitFace_button').prop('disabled', false);
        return;
    }

    // Prepare FormData
    var formData = new FormData();
    formData.append('image', fileInput.files[0]);
    formData.append('user_id', UserId_Global);
    var url = operatorFaceRegisterUrl.replace(':id', UserId_Global);
    StartLoading();

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            StopLoading();
            if (response.status == 1) {
                UserId_Global = 0;
                table.ajax.reload();
                showAlert('success', response.message);
                $('#image').val('');
                $('.frm-btn-close').trigger('click');
                $('#faceRegisterModal').modal('hide');

            } else {
                showAlert('error', response.message);
            }
            $('#submitFace_button').prop('disabled', false);
        },
        error: function (xhr) {
            StopLoading();
            showAlert('error', 'Internal server error!');
            $('#submitFace_button').prop('disabled', false);
        }
    });
}


function showChangePasswordForm(id, role) {

    UserId_Global = id;
    UserRole_Global = role;

    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.change_password_form_data').show();
    $('.heading_title').text('Change Password');
}


function changePassword() {
    $('.error').text('');
    $('#password_submit_button').prop('disabled', true);


    // Validate form fields
    var isValid = validateChangePasswordForm();
    var formData = new FormData($('#changePasswordForm')[0]);
    if (!isValid) {
        $('#password_submit_button').prop('disabled', false);
        return;
    }

    var url = UserRole_Global == 'operator' ? operatorChangePasswordUrl : userChangePasswordUrl;
    url = url.replace(':id', UserId_Global);


    StartLoading();
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            StopLoading();
            if (response.status == 1) {

                showAlert('success', response.message);

                resetChangePasswordForm();
            } else {
                // Handle validation errors
                showAlert('error', response.message);
            }
            $('#password_submit_button').prop('disabled', false);
        },
        error: function (xhr) {
            StopLoading();
            showAlert('error', 'Internal server error!');
            $('#password_submit_button').prop('disabled', false);

            //console.log(xhr.responseText);
        }
    });
}

function validateChangePasswordForm() {
    var isValid = true;
    $('#current_password_error').text('');

    // New Password
    var newPass = $('#new_password').val().trim();
    if (newPass === '') {
        $('#new_password_error').text('New Password is required.');
        isValid = false;
    } else if (newPass.length < 6) {
        $('#new_password_error').text('New Password must be at least 6 characters.');
        isValid = false;
    }

    // Confirm Password
    var confirmPass = $('#new_password_confirmation').val().trim();
    if (confirmPass === '') {
        $('#new_password_confirmation_error').text('Confirm Password is required.');
        isValid = false;
    } else if (newPass !== confirmPass) {
        $('#new_password_confirmation_error').text('Confirm Password must match New Password.');
        isValid = false;
    }



    return isValid;
}


function resetChangePasswordForm() {
    $('#changePasswordForm')[0].reset();
    $('.error').text('');
    $('.heading_title').text('Users');
    $('.change_password_form_data').hide();
    $('.data_list').show();
    UserId_Global = 0;
    UserRole_Global = 0;
}
