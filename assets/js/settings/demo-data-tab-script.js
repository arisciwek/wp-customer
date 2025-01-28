jQuery(document).ready(function($) {
    $('.generate-demo-data').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const type = button.data('type');
        const nonce = button.data('nonce');
        
        // Disable button while processing
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_demo_data',
                type: type,
                nonce: nonce
            },
            success: function(response) {
                // Re-enable button
                button.prop('disabled', false);
                
                const messageDiv = $('#demo-data-messages');
                messageDiv.removeClass('notice-error notice-success notice-warning').empty();
                
                if (response.success) {
                    messageDiv.addClass('notice notice-success').html('<p>' + response.data.message + '</p>');
                } else {
                    // Different styling for development mode off vs other errors
                    if (response.data.type === 'dev_mode_off') {
                        messageDiv.addClass('notice notice-warning').html('<p>' + response.data.message + '</p>');
                    } else {
                        messageDiv.addClass('notice notice-error').html('<p>' + response.data.message + '</p>');
                    }
                }
            },
            error: function() {
                // Re-enable button
                button.prop('disabled', false);
                
                const messageDiv = $('#demo-data-messages');
                messageDiv.removeClass('notice-error notice-success notice-warning')
                    .addClass('notice notice-error')
                    .html('<p>An unexpected error occurred while generating demo data.</p>');
            }
        });
    });
});
