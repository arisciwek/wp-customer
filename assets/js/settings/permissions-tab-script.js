/**
 * Permission Matrix Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/permissions-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *              Terintegrasi dengan modal konfirmasi dan toast notifications
 *
 * Dependencies:
 * - jQuery
 * - wpCustomerToast
 * - WIModal component
 *
 * Changelog:
 * 1.0.1 - 2024-12-08
 * - Replaced native confirm with WIModal for reset confirmation
 * - Added warning type modal styling
 * - Enhanced UX for reset operation
 * - Improved error handling and feedback
 *
 * 1.0.0 - 2024-12-02
 * - Initial implementation
 * - Basic permission matrix handling
 * - AJAX integration
 * - Toast notifications
 */
(function($) {
    'use strict';

    const PermissionMatrix = {
        init() {
            this.bindEvents();
            this.initTooltips();
            this.initResetButton();
        },

        bindEvents() {
            // Add any UI event handlers here
            $('#wp-customer-permissions-form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true);
            });
        },

        initTooltips() {
            if ($.fn.tooltip) {
                $('.tooltip-icon').tooltip({
                    position: { my: "center bottom", at: "center top-10" }
                });
            }
        },
        
        initResetButton() {
            const self = this;
            $('#reset-permissions-btn').on('click', function(e) {
                e.preventDefault();
                
                // Show confirmation modal
                WIModal.show({
                    title: 'Reset Permissions?',
                    message: 'This will restore all permissions to their default settings. This action cannot be undone.',
                    icon: 'alert-triangle',
                    type: 'warning',
                    confirmText: 'Reset Permissions',
                    confirmClass: 'button-warning',
                    cancelText: 'Cancel',
                    onConfirm: () => self.performReset()
                });
            });
        },

        performReset() {
            const $button = $('#reset-permissions-btn');
            const originalText = $button.text();
            //console.log('Nonce value:', wpCustomerData.nonce);
            // Set loading state
            $button.addClass('loading')
                   .prop('disabled', true)
                   .html(`<i class="dashicons dashicons-update"></i> Resetting...`);

            // Perform AJAX reset
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_permissions',
                    nonce: wpCustomerData.nonce
                },
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    if (response.success) {
                        wpCustomerToast.success(response.data.message || 'Permissions reset successfully');
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        console.error('Error in AJAX response:', response);
                        wpCustomerToast.error(response.data.message || 'Failed to reset permissions');
                        // Reset button state
                        $button.removeClass('loading')
                               .prop('disabled', false)
                               .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                    }
                },
                error: function(xhr, status, error) {                    
                    wpCustomerToast.error('Server error while resetting permissions: ' + xhr.status + ' ' + xhr.statusText);
                    // Reset button state
                    $button.removeClass('loading')
                           .prop('disabled', false)
                           .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('#wp-customer-permissions-form').length) {
            PermissionMatrix.init();
        }
    });

})(jQuery);
