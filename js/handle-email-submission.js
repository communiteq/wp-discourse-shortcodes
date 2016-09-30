jQuery(document).ready(function () {
    jQuery('.discourse-email-message').submit(function (event) {
        var url = handle_email_submission_script.ajaxurl,
            action = 'process_discourse_email',
            $this = jQuery(this),
            formName = $this.find('input[name=discourse_email_form_name]').val(),
            nonce = $this.find('#' + formName).val(),

            to = $this.find('input[name=discourse_to_address]').val(),
            from = $this.find('input[name=user_email]').val(),
            subject = $this.find('input[name=discourse_email_subject]').val(),
            prefilledMessage = $this.find('input[name=discourse_email_message]').val(),
            composedMessage = $this.find('textarea[name=discourse_email_message]').val();

            data = {
                'formName': formName,
                'nonce': nonce,
                'action': action,
                'to': to,
                'from': from,
                'subject': subject,
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