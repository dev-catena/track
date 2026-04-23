$(document).ready(function() {
    var interval = setInterval(function() {
        var elem = $('body');
        if (elem.length && elem.css('top') === '40px') {
            elem.css('top', '');
            clearInterval(interval);
        }
    }, 100);
});



function showAlert(type, message) {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: 3000,
        positionClass: "toast-top-right",
    };

    switch (type) {
        case 'success':
            toastr.success(message);
            break;
        case 'error':
            toastr.error(message);
            break;
        case 'warning':
            toastr.warning(message);
            break;
        case 'info':
            toastr.info(message);
            break;
        default:
            toastr.info(message);
            break;
    }
}

function StartLoading() {
    $('#loader').css('display', 'flex').hide().fadeIn(150);
}

function StopLoading() {
    $('#loader').fadeOut(150);
}

function confirmAndDelete(url, table, data = null) {
    Swal.fire({
        title: data?.title || 'Are you sure?',
        text: data?.message || "This action cannot be undone.",
        icon: data?.icon || 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0c5389',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            deleteData(url,table); // Call actual delete
        }
    });
}

function deleteData(url,table) {
    StartLoading();

    $.ajax({
        url: url,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                showAlert('success', response.message || 'Data deleted successfully.');

                table.ajax.reload();
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

function validateEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
function validatePhone(phone) {
    var re = /^\+?[0-9]{10,15}$/;
    return re.test(phone);
}

function applyTheme(theme) {
    $('body').removeClass('white-content dark-content').addClass(theme);
    applyThemeUi(theme);
}

function applyThemeUi(theme) {
    if (theme === 'dark-content') {
        $('#darkIcon').addClass('d-none');
        $('#lightIcon').removeClass('d-none');
        $('.dark-logo').removeClass('active');
        $('.light-logo').addClass('active');
    } else {
        $('#lightIcon').addClass('d-none');
        $('#darkIcon').removeClass('d-none');
        $('.dark-logo').addClass('active');
        $('.light-logo').removeClass('active');
    }
}

function setThemeCookie(theme) {
    $.ajax({
        url: setThemeUrl,
        method: 'POST',
        data: {
            theme: theme,
            _token: $('meta[name="csrf-token"]').attr('content')
        }
    });
}

function setGoogleTranslate(lang) {
    const cookieValue = '/en/' + lang;

    document.cookie.split(";").forEach(function(c) {
        const name = c.split("=")[0].trim();
        if (name === "googtrans") {
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax";
        }
    });

    // Remove cookie for parent domain
    document.cookie = "googtrans=; path=/; domain=cloudwaysapps.com; expires=Thu, 01 Jan 1970 00:00:00 GMT";

    document.cookie = "googtrans=" + cookieValue + ";path=/;SameSite=Lax";

    location.reload();
}

/**
 * Alinha o campo Empresa dos formulários CRUD com o seletor global do menu (#global-org-select).
 * Superadmin: select#organization_id (usuários, docas) ou input hidden + #organization_display (departamentos).
 */
function syncFormOrganizationFromGlobalSelector() {
    if (!$('#global-org-select').length) {
        return;
    }
    var orgId = $('#global-org-select').val();
    if (!orgId) {
        return;
    }
    var orgName = ($('#global-org-select option:selected').text() || '').trim();

    var $orgField = $('#organization_id');
    if (!$orgField.length) {
        return;
    }

    if ($orgField.is('select')) {
        $orgField.val(orgId);
        if ($orgField.val() !== String(orgId)) {
            return;
        }
        $orgField.trigger('change');
        return;
    }

    if ($orgField.is('input')) {
        $orgField.val(orgId);
        if ($('#organization_display').length) {
            $('#organization_display').val(orgName);
        }
    }
}

$(document).on('organization-changed', function () {
    syncFormOrganizationFromGlobalSelector();
});

function getDepartmentListByCompanyId(id,page = null,table = null) {
    var url = deptByCompanyListUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if(page == 'logs'){
                $('#departmentFilter').empty();
                $('#departmentFilter').append('<option value="">All Departments</option>');
            } else {
                $('#department_id').empty();
                $('#department_id').append('<option value="">Select Department</option>');

            }

            if (response.status == 1) {
                var dept = response.data;


                // Loop and append new options

                $.each(dept, function (index, item) {
                    if(page == 'logs'){
                        $('#departmentFilter').append(
                            $('<option>', {
                                value: item.id,
                                text: item.name
                            })
                        );
                    } else {
                        $('#department_id').append(
                            $('<option>', {
                                value: item.id,
                                text: item.name
                            })
                        );
                    }

                });


            } else {
                //showAlert('warning', response.message || 'Something went wrong.');
            }

            if(page == 'logs'){
                table.ajax.reload();
            }
        },
        error: function () {
            StopLoading();
            showAlert('error', 'Internal server error!');
            table.ajax.reload();
        }
    });
}

function escapeCSV(value) {
    if (value == null) return "";
    value = value.toString().trim();
    if (value.includes(",") || value.includes("\n") || value.includes('"')) {
        value = '"' + value.replace(/"/g, '""') + '"';
    }
    return value;
}
