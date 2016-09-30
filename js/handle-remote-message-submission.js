jQuery(document).ready(function () {
    jQuery('.discourse-remote-message').submit(function (event) {
        var url = handle_remote_message_submission_script.ajaxurl,
            action = 'process_discourse_remote_message',
            $this = jQuery(this),
            formName = $this.find('input[name=discourse_email_form_name]').val(),
            nonce = $this.find('#' + formName).val(),

            userEmail = $this.find('input[name=user_email]').val(),
            title = $this.find('input[name=discourse_email_subject]').val(),
            prefilledMessage = $this.find('input[name=discourse_email_message]').val(),
            composedMessage = $this.find('textarea[name=discourse_email_message]').val();

            data = {
                'formName': formName,
                'nonce': nonce,
                'action': action,
                'userEmail': userEmail,
                'title': title,
                'prefilledMessage': prefilledMessage,
                'composedMessage': composedMessage
            };

            console.log(url);
            jQuery.post(url, data, function(response) {
                console.log(response);
            });


        event.preventDefault();
    });
});