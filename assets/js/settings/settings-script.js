/**
 * Customer Settings JavaScript - GLOBAL SCOPE
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/settings-script.js
 *
 * Description: Global JavaScript for customer settings functionality.
 *              Handles page-level Save & Reset buttons across ALL tabs.
 *              Based on wp-app-core pattern (TODO-2198)
 *
 * Changelog:
 * 2.0.0 - 2025-01-13 (TODO-2198)
 * - BREAKING: Complete refactor to match wp-app-core pattern
 * - Added global save button handler (#wpc-settings-save)
 * - Added global reset button handler (#wpc-settings-reset)
 * - Uses WPModal for reset confirmation
 * - Native form POST (no AJAX)
 * - Removed deprecated AJAX handlers
 *
 * 1.0.0 - 2024-11-26
 * - Initial implementation
 */

(function($) {
    'use strict';

    const WPCustomerSettings = {
        init: function() {
            console.log('[WPC Settings] 🔄 Initializing global settings handler...');
            console.log('[WPC Settings] Current URL:', window.location.href);

            const $saveBtn = $('#wpc-settings-save');
            const $resetBtn = $('#wpc-settings-reset');

            console.log('[WPC Settings] Save button found:', $saveBtn.length > 0, {
                exists: $saveBtn.length > 0,
                visible: $saveBtn.is(':visible'),
                formId: $saveBtn.data('form-id'),
                currentTab: $saveBtn.data('current-tab')
            });

            console.log('[WPC Settings] Reset button found:', $resetBtn.length > 0, {
                exists: $resetBtn.length > 0,
                visible: $resetBtn.is(':visible'),
                formId: $resetBtn.data('form-id'),
                currentTab: $resetBtn.data('current-tab')
            });

            this.bindEvents();
        },

        bindEvents: function() {
            // GLOBAL SCOPE: Page-level Save button
            const $saveBtn = $('#wpc-settings-save');
            if ($saveBtn.length === 0) {
                console.warn('[WPC Settings] ⚠️ Save button not found!');
            } else {
                $saveBtn.on('click', this.handleGlobalSave.bind(this));
                console.log('[WPC Settings] ✅ Global save button handler registered');
            }

            // GLOBAL SCOPE: Page-level Reset button
            const $resetBtn = $('#wpc-settings-reset');
            if ($resetBtn.length === 0) {
                console.warn('[WPC Settings] ⚠️ Reset button not found!');
            } else {
                $resetBtn.on('click', this.handleGlobalReset.bind(this));
                console.log('[WPC Settings] ✅ Global reset button handler registered');
            }
        },

        /**
         * Handle global Save button click
         * Submits the form for the current active tab
         */
        handleGlobalSave: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const formId = $btn.data('form-id');
            const currentTab = $btn.data('current-tab');

            console.log('[WPC Settings] Global save clicked:', {
                tab: currentTab,
                formId: formId
            });

            // Find and submit the form
            const $form = $('#' + formId);

            if ($form.length === 0) {
                console.error('[WPC Settings] Form not found:', formId);
                alert('Error: Form tidak ditemukan untuk tab "' + currentTab + '"');
                return false;
            }

            console.log('[WPC Settings] Submitting form:', formId);

            // Disable button to prevent double-submit
            $btn.prop('disabled', true).text('Menyimpan...');

            // Ensure saved_tab input exists and has correct value
            $form.find('input[name="saved_tab"]').val(currentTab);

            console.log('[WPC Settings] 📍 Set saved_tab value:', currentTab);

            // Submit the form (WordPress will handle it)
            $form.submit();

            return false;
        },

        /**
         * Handle global Reset button click
         * Shows WPModal confirmation then submits form with reset flag
         */
        handleGlobalReset: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const title = $btn.data('reset-title') || 'Reset ke Default?';
            const message = $btn.data('reset-message') || 'Apakah Anda yakin ingin mereset semua pengaturan ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.';
            const formId = $btn.data('form-id');
            const currentTab = $btn.data('current-tab');

            console.log('[WPC Settings] Reset clicked:', {
                tab: currentTab,
                formId: formId,
                title: title
            });

            // Check if WPModal is loaded
            if (typeof WPModal === 'undefined') {
                console.error('[WPC Settings] WPModal not loaded!');
                // Fallback to native confirm
                if (confirm(message)) {
                    this.submitResetForm(formId, currentTab);
                }
                return;
            }

            // Show WPModal confirmation
            WPModal.confirm({
                title: title,
                message: message,
                danger: true,
                confirmLabel: 'Reset Settings',
                onConfirm: () => {
                    console.log('[WPC Settings] Confirmed - submitting reset form');
                    this.submitResetForm(formId, currentTab);
                }
            });
        },

        /**
         * Submit form with reset flag
         */
        submitResetForm: function(formId, currentTab) {
            const $form = $('#' + formId);

            if ($form.length === 0) {
                console.error('[WPC Settings] Form not found:', formId);
                alert('Error: Form tidak ditemukan. Silakan refresh halaman.');
                return;
            }

            // Set reset flag to 1
            $form.find('input[name="reset_to_defaults"]').val('1');

            // Set current tab for reset
            $form.find('input[name="current_tab"]').val(currentTab);
            $form.find('input[name="saved_tab"]').val(currentTab);

            console.log('[WPC Settings] Submitting reset form via POST to options.php');

            // Submit form - will reload page with success/error message
            $form.submit();
        },

        /**
         * Show admin notice
         * @param {string} message Notice message
         * @param {string} type Notice type (success, error, warning, info)
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WPCustomerSettings.init();
    });

    // Export to global scope
    window.WPCustomerSettings = WPCustomerSettings;

})(jQuery);
