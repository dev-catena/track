var SystemId_Global = 0;

function updateSystemSetting() {
    $('.error').text(''); // Clear previous errors
    $('#submit_button').prop('disabled', true);

    var formData = new FormData($('#form')[0]);

    SystemId_Global = $('#system_id').val();
    url = systemSettingUpdateUrl.replace(':id', SystemId_Global);
    formData.append('_method', 'PUT');


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
                //resetForm();
                respTheme = response.data.theme;
                var themeData = respTheme == 'light' ? 'white-content' : 'dark-content';
                let theme = Cookies.get('theme');

                if (!theme || theme != themeData) {
                    theme = themeData;
                    applyTheme(theme);

                    setThemeCookie(theme);
                }

                var lang = response.data.language || 'en';
                setGoogleTranslate(lang);

            } else {
                // Handle validation errors
                showAlert('error', response.message);
            }
            $('#submit_button').prop('disabled', false);
        },
        error: function(xhr) {
            StopLoading();
            showAlert('error', 'Internal server error!');
            $('#submit_button').prop('disabled', false);

            //console.log(xhr.responseText);
        }
    });
}
