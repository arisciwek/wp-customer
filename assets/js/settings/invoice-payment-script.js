/**
 * Invoice & Payment Settings JavaScript
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/invoice-payment-script.js
 *
 * Description: JavaScript untuk tab Invoice & Payment Settings
 *              Handle dynamic reminder days dan form validation
 *
 * Changelog:
 * v1.0.0 - 2025-10-17 (Task-2158)
 * - Initial version
 * - Add reminder days add/remove functionality
 * - Add form validation
 * - Add payment methods validation
 */

(function($) {
    'use strict';

    const InvoicePaymentSettings = {
        init: function() {
            this.bindEvents();
            this.addRemoveButtons();
        },

        bindEvents: function() {
            // Add reminder day
            $('#add-reminder-day').on('click', this.addReminderDay.bind(this));

            // Remove reminder day (delegated event)
            $(document).on('click', '.remove-reminder', this.removeReminderDay.bind(this));

            // Form validation
            $('#wp-customer-invoice-payment-form').on('submit', this.validateForm.bind(this));

            // Payment methods validation
            $('input[name="wp_customer_invoice_payment_options[payment_methods][]"]')
                .on('change', this.validatePaymentMethods.bind(this));
        },

        /**
         * Add remove buttons to existing reminder days
         */
        addRemoveButtons: function() {
            const $rows = $('.reminder-day-row');

            // Only add remove button if more than 1 row
            if ($rows.length > 1) {
                $rows.each(function() {
                    const $row = $(this);
                    if (!$row.find('.remove-reminder').length) {
                        $row.find('label').append(
                            '<a href="#" class="remove-reminder">' +
                            '<span class="dashicons dashicons-no-alt"></span> Hapus' +
                            '</a>'
                        );
                    }
                });
            }
        },

        /**
         * Add new reminder day row
         */
        addReminderDay: function(e) {
            e.preventDefault();

            const $container = $('.reminder-days-container');
            const $newRow = $('<div class="reminder-day-row"></div>');

            $newRow.html(
                '<label>' +
                'H-<input type="number" ' +
                'name="wp_customer_invoice_payment_options[payment_reminder_days][]" ' +
                'value="1" ' +
                'min="1" ' +
                'max="365" ' +
                'class="small-text"> ' +
                'hari sebelum jatuh tempo ' +
                '<a href="#" class="remove-reminder">' +
                '<span class="dashicons dashicons-no-alt"></span> Hapus' +
                '</a>' +
                '</label>'
            );

            $container.append($newRow);

            // Add remove buttons to all rows if not exist
            this.addRemoveButtons();

            // Focus on new input
            $newRow.find('input').focus();
        },

        /**
         * Remove reminder day row
         */
        removeReminderDay: function(e) {
            e.preventDefault();

            const $rows = $('.reminder-day-row');

            // Don't allow removing if only 1 row left
            if ($rows.length <= 1) {
                alert('Minimal 1 jadwal reminder harus ada.');
                return;
            }

            const $row = $(e.target).closest('.reminder-day-row');
            $row.fadeOut(300, function() {
                $(this).remove();

                // Update remove buttons visibility
                InvoicePaymentSettings.updateRemoveButtons();
            });
        },

        /**
         * Update remove buttons visibility based on row count
         */
        updateRemoveButtons: function() {
            const $rows = $('.reminder-day-row');
            const $removeButtons = $('.remove-reminder');

            if ($rows.length <= 1) {
                $removeButtons.hide();
            } else {
                $removeButtons.show();
            }
        },

        /**
         * Validate payment methods - at least one must be selected
         */
        validatePaymentMethods: function() {
            const $checkboxes = $('input[name="wp_customer_invoice_payment_options[payment_methods][]"]');
            const checkedCount = $checkboxes.filter(':checked').length;

            if (checkedCount === 0) {
                // Prevent unchecking the last checkbox
                alert('Minimal 1 metode pembayaran harus dipilih.');
                $(this).prop('checked', true);
                return false;
            }

            return true;
        },

        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            let isValid = true;
            let errorMessage = '';

            // Validate invoice due days
            const dueDays = parseInt($('#invoice_due_days').val());
            if (dueDays < 1 || dueDays > 365) {
                isValid = false;
                errorMessage += 'Jatuh tempo harus antara 1-365 hari.\n';
            }

            // Validate invoice prefix
            const prefix = $('#invoice_prefix').val().trim();
            if (prefix === '') {
                isValid = false;
                errorMessage += 'Prefix invoice tidak boleh kosong.\n';
            }

            // Validate currency
            const currency = $('#invoice_currency').val().trim();
            if (currency === '') {
                isValid = false;
                errorMessage += 'Mata uang tidak boleh kosong.\n';
            }

            // Validate tax percentage
            const tax = parseFloat($('#invoice_tax_percentage').val());
            if (tax < 0 || tax > 100) {
                isValid = false;
                errorMessage += 'PPN harus antara 0-100%.\n';
            }

            // Validate payment methods
            const checkedMethods = $('input[name="wp_customer_invoice_payment_options[payment_methods][]"]:checked');
            if (checkedMethods.length === 0) {
                isValid = false;
                errorMessage += 'Minimal 1 metode pembayaran harus dipilih.\n';
            }

            // Validate auto-approve threshold
            const threshold = parseFloat($('#payment_auto_approve_threshold').val());
            if (threshold < 0) {
                isValid = false;
                errorMessage += 'Auto-approve threshold tidak boleh negatif.\n';
            }

            // Validate reminder days
            const $reminderInputs = $('input[name="wp_customer_invoice_payment_options[payment_reminder_days][]"]');
            let hasInvalidReminder = false;

            $reminderInputs.each(function() {
                const val = parseInt($(this).val());
                if (val < 1 || val > 365) {
                    hasInvalidReminder = true;
                    return false; // break loop
                }
            });

            if (hasInvalidReminder) {
                isValid = false;
                errorMessage += 'Jadwal reminder harus antara 1-365 hari.\n';
            }

            // Show error message if validation fails
            if (!isValid) {
                e.preventDefault();
                alert('Validasi gagal:\n\n' + errorMessage);
                return false;
            }

            return true;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the invoice-payment tab
        if ($('#wp-customer-invoice-payment-form').length) {
            InvoicePaymentSettings.init();
        }
    });

})(jQuery);
