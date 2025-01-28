jQuery(document).ready(function($) {
    $('.generate-demo-data').on('click', function() {
        const button = $(this);
        const type = button.data('type');
        const nonce = button.data('nonce');
        
        // Disable button
        button.prop('disabled', true);
        
        // Add spinner
        button.after('<span class="spinner is-active"></span>');

        // AJAX call
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_demo_data',
                type: type,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#demo-data-messages').html(
                        '<div class="notice notice-success is-dismissible"><p>' + 
                        response.data.message + 
                        '</p></div>'
                    );
                } else {
                    $('#demo-data-messages').html(
                        '<div class="notice notice-error is-dismissible"><p>' + 
                        (response.data.message || 'An error occurred while generating demo data.') + 
                        '</p></div>'
                    );
                }
            },
            error: function() {
                $('#demo-data-messages').html(
                    '<div class="notice notice-error is-dismissible"><p>' + 
                    'An error occurred while generating demo data.' + 
                    '</p></div>'
                );
            },
            complete: function() {
                // Re-enable button and remove spinner
                button.prop('disabled', false);
                button.next('.spinner').remove();
            }
        });
    });

    // Add handler for dismissible notices
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
});
