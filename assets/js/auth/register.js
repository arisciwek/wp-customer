/**
 * Customer Registration Form Handler
 * 
 * @package     WP_Customer
 * @subpackage  Assets/JS/Auth
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/assets/js/auth/register.js
 * 
 * Description: Menangani form registrasi customer:
 *              - AJAX submission
 *              - Validasi form
 *              - Format NPWP
 *              - Toast notifications
 * 
 * Dependencies:
 * - jQuery
 * - wp-customer-toast
 * - WordPress AJAX
 *
 * Last modified: 2024-01-11
 */
(function($) {
    'use strict';

    // Main registration module
    const CustomerRegistration = {
        init() {
            this.form = $('#customer-register-form');
            this.submitButton = this.form.find('button[type="submit"]');
            
            this.bindEvents();
        },

        bindEvents() {
            // NPWP formatter removed - now handled by customer-form-auto-format.js
            // NIB formatter removed - now handled by customer-form-auto-format.js

            // Form submission
            this.form.on('submit', this.handleSubmit.bind(this));
        },

        handleSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(this.form[0]);
            formData.append('action', 'wp_customer_register');
            
            this.submitButton
                .prop('disabled', true)
                .text(wpCustomerData.i18n.registering || 'Mendaftar...');

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: this.handleComplete.bind(this)
            });
        },

        handleSuccess(response) {
            if (response.success) {
                wpCustomerToast.success(response.data.message);
                setTimeout(() => {
                    window.location.href = response.data.redirect;
                }, 1500);
            } else {
                wpCustomerToast.error(response.data.message);
            }
        },

        handleError() {
            wpCustomerToast.error(
                wpCustomerData.i18n.error || 'Terjadi kesalahan. Silakan coba lagi.'
            );
        },

        handleComplete() {
            this.submitButton
                .prop('disabled', false)
                .text(wpCustomerData.i18n.register || 'Daftar');
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        CustomerRegistration.init();
    });

})(jQuery);
