jQuery(document).ready(function () {
    jQuery('.discourse-remote-message').submit(function (event) {
        var url = handle_remote_message_submission_script.ajaxurl,
            action = 'process_discourse_remote_message',
            $this = jQuery(this),
            formName = $this.find('input[name=discourse_remote_message_form_name]').val(),
            nonce = $this.find('#' + formName).val(),

            userEmail = $this.find('input[name=user_email]').val(),
            title = $this.find('input[name=discourse_remote_message_title]').val(),
            message = $this.find('input[name=discourse_remote_message]').val(),
            data = {
                'action': action,
                'form_name': formName,
                'nonce': nonce,
                'user_email': userEmail,
                'title': title,
                'message': message
            };


            jQuery.post(url, data, function(response) {
                console.log(response);
            });


        event.preventDefault();
    });
});