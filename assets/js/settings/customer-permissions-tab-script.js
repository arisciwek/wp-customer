/**
 * Permission Matrix Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/customer-permissions-tab-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *              Terintegrasi dengan modal konfirmasi dan toast notifications
 *              INCLUDES RACE CONDITION PROTECTION
 *
 * Dependencies:
 * - jQuery
 * - wpCustomerToast (if available)
 * - WIModal component (if available)
 *
 * Changelog:
 * 1.0.1 - 2025-10-29
 * - CRITICAL HOTFIX: Fixed checkbox disable timing bug
 * - Split lockPage() into lockPageForSave() and lockPageForReset()
 * - lockPageForSave(): Disables buttons only (checkboxes must be enabled for form submit)
 * - lockPageForReset(): Disables everything (safe for AJAX operation)
 * - Fixed bug: disabled checkboxes were not being submitted in POST data
 *
 * 1.0.0 - 2025-10-29
 * - Initial implementation with race condition protection
 * - Cross-disable buttons (reset disables save, save disables reset)
 * - lockPage() method to prevent concurrent operations
 * - unlockPage() for error recovery
 * - Disabled all checkboxes during reset/save operations
 * - Page-level loading state
 */
(function($) {
    'use strict';

    const PermissionMatrix = {
        init() {
            this.bindEvents();
            this.initTooltips();
            this.initResetButton();
        },

        /**
         * Lock page for form submission
         * Disables buttons only - checkboxes must remain enabled for form data
         */
        lockPageForSave() {
            // Disable ALL buttons (reset + save)
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', true);

            // DO NOT disable checkboxes - they need to be submitted!
            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Lock page for reset operation
         * Disables everything including checkboxes (AJAX operation, no form submit)
         */
        lockPageForReset() {
            // Disable ALL buttons (reset + save)
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', true);

            // Disable ALL checkboxes (safe for AJAX, not form submit)
            $('.permission-checkbox').prop('disabled', true);

            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Unlock page (for error recovery only)
         */
        unlockPage() {
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', false);
            $('.permission-checkbox').prop('disabled', false);
            $('body').removeClass('permission-operation-in-progress');
        },

        bindEvents() {
            const self = this;

            // Handle form submission with race condition protection
            $('#wp-customer-permissions-form').on('submit', function(e) {
                // Lock page for save (buttons only, NOT checkboxes)
                // Checkboxes must remain enabled so browser can serialize form data
                self.lockPageForSave();

                // Note: Form will continue submitting, page will be locked until reload
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

                // Check if WIModal is available
                if (typeof WIModal !== 'undefined') {
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
                } else {
                    // Fallback to native confirm
                    if (confirm('Are you sure you want to reset all permissions to default? This action cannot be undone.')) {
                        self.performReset();
                    }
                }
            });
        },

        performReset() {
            const self = this;
            const $button = $('#reset-permissions-btn');
            const $icon = $button.find('.dashicons');
            const originalText = $button.text();

            // CRITICAL: Lock entire page to prevent race conditions
            // Use lockPageForReset() - disables checkboxes too (safe for AJAX)
            self.lockPageForReset();

            // Set loading state on reset button
            $button.addClass('loading')
                   .html(`<i class="dashicons dashicons-update"></i> Resetting...`);

            // Perform AJAX reset
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_customer_permissions',
                    nonce: wpCustomerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Check if toast is available
                        if (typeof wpCustomerToast !== 'undefined') {
                            wpCustomerToast.success(response.data.message || 'Permissions reset successfully');
                        }
                        // Reload page with parameter to clear stale notices
                        // Remove old save notice and mark as reset operation
                        setTimeout(() => {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('settings-updated'); // Remove old save notice
                            url.searchParams.set('permissions-reset', '1'); // Mark as reset operation
                            window.location.href = url.toString();
                        }, 500); // Small delay to show toast
                    } else {
                        // IMPORTANT: Clear old PHP notices before showing error
                        $('.wrap .notice').remove();

                        // Show error
                        if (typeof wpCustomerToast !== 'undefined') {
                            wpCustomerToast.error(response.data.message || 'Failed to reset permissions');
                        } else {
                            alert(response.data.message || 'Failed to reset permissions');
                        }
                        // Unlock page on error
                        self.unlockPage();
                        // Reset button state
                        $button.removeClass('loading')
                               .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                    }
                },
                error: function() {
                    // IMPORTANT: Clear old PHP notices before showing error
                    $('.wrap .notice').remove();

                    // Show error
                    if (typeof wpCustomerToast !== 'undefined') {
                        wpCustomerToast.error('Server error while resetting permissions');
                    } else {
                        alert('Server error while resetting permissions');
                    }
                    // Unlock page on error
                    self.unlockPage();
                    // Reset button state
                    $button.removeClass('loading')
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
