var BotId_Global = 0;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});


var table = $('#bots-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: botListUrl,
    columns: [
        { data: 'key', name: 'key' },
        { data: 'value', name: 'value' },
        { data: 'type', name: 'type' },
        { data: 'category', name: 'category' },
        { data: 'description', name: 'description' },
        { data: 'updated_at', name: 'updated_at' },
        { data: 'actions', name: 'actions'}
    ],
    paging: true,
    ordering: false,
    dom: '',
});



function showForm(mode) {
    $('.main-panel').animate({ scrollTop: 0 }, 500);
    $('.data_list').hide();
    $('.form_data').show();
    if(mode == 1) {
        $('#update_button').hide();
        $('#submit_button').show();
        $('.heading_title').text('Add Bot Configuration');
    } else {
        $('#submit_button').hide();
        $('#update_button').show();

        $('.heading_title').text('Update Bot Configuration');

    }
}

function resetForm() {
    $('#form')[0].reset();
    $('#key').prop('readonly',false);
    $('#description').text('');
    $('.error').text('');
    $('.heading_title').text('Bots Configuration');
    $('.form_data').hide();
    $('.data_list').show();
    BotId_Global = 0;
}


function addUpdateBot(mode) {
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
        url = botSaveUrl;
    } else {

        url = botUpdateUrl.replace(':id', BotId_Global);
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

    if ($('#key').val().trim() === '') {
        $('#key_error').text('Key is required.');
        isValid = false;
    }

    if ($('#value').val().trim() === '') {
        $('#value_error').text('Value is required.');
        isValid = false;
    }
    if ($('#category').val().trim() === '') {
        $('#category_error').text('Category is required.');
        isValid = false;
    }
    if ($('#type').val().trim() === '') {
        $('#type_error').text('Type is required.');
        isValid = false;
    }


    return isValid;
}



function deleteBot(id) {
    var url = botDeleteUrl.replace(':id', id);
    confirmAndDelete(url,table);

    // setTimeout(() => {
    //     location.reload();
    // }, 1000);
}

function getBotDetail(id) {
    var url = botDetailUrl.replace(':id', id);

    StartLoading();

    $.ajax({
        url: url,
        type: 'get',
        success: function (response) {
            StopLoading();

            if (response.status == 1) {
                var bot = response.data;
                $('#key').val(bot.key);
                $('#value').val(bot.value);
                $('#type').val(bot.type).trigger('change');
                $('#category').val(bot.category).trigger('change');
                $('#description').text(bot.description);
                $('#key').prop('readonly',true);
                BotId_Global = bot.id;
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




$('#key').on('input', function() {
    $(this).val($(this).val().replace(/[^A-Za-z_]/g, ''));
});

