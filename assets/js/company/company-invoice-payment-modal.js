/**
 * Company Invoice Payment Modal
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-invoice-payment-modal.js
 *
 * Description: Payment modal handler untuk invoice payment.
 *              Extracted dari company-membership.js dan adapted
 *              untuk invoice payment flow.
 *              Menangani tampilan modal, validasi input, dan
 *              submit pembayaran via AJAX.
 *              Now uses PHP template instead of JavaScript HTML string.
 *
 * Dependencies:
 * - jQuery
 * - WordPress AJAX
 * - wpCustomerData (nonce, ajaxUrl)
 * - Template: membership-invoice-payment-modal.php (server-side rendered)
 *
 * Changelog:
 * 1.1.0 - 2025-01-17 (Review-07)
 * - Refactored to use PHP template instead of JavaScript HTML string
 * - Improved separation of concerns (HTML in template, logic in JS)
 * - Better internationalization support
 * - Easier to maintain and modify
 * - Modal is now pre-rendered on page load, just populated with data
 *
 * 1.0.0 - 2024-01-10
 * - Initial version
 * - Payment modal for invoice
 * - AJAX payment processing
 * - Success/error handling
 */

(function($) {
    'use strict';

    const InvoicePaymentModal = {
        /**
         * Show payment modal for invoice
         * Now uses pre-rendered PHP template, just populates data
         *
         * @param {number} invoiceId Invoice ID
         * @param {string} invoiceNumber Invoice number
         * @param {number} amount Invoice amount
         */
        showPaymentModal(invoiceId, invoiceNumber, amount) {
            // Populate modal with invoice data
            $('#payment-invoice-number').text(invoiceNumber);
            $('#payment-invoice-amount').text('Rp ' + this.formatCurrency(amount));

            // Set data attributes on confirm button
            $('#payment-confirm-btn')
                .attr('data-invoice-id', invoiceId)
                .attr('data-invoice-number', invoiceNumber)
                .attr('data-amount', amount);

            // Reset payment method to first option
            $('#payment-method').val('transfer_bank');

            // Show modal
            $('#invoice-payment-modal').show();

            // Bind modal events
            this.bindModalEvents();
        },

        /**
         * Bind modal event handlers
         */
        bindModalEvents() {
            const self = this;

            // Close button
            $('.modal-close, .modal-cancel').off('click').on('click', () => {
                $('#invoice-payment-modal').hide();
            });

            // Confirm button
            $('.modal-confirm').off('click').on('click', function(e) {
                const $button = $(this);
                const invoiceId = $button.data('invoice-id');
                const invoiceNumber = $button.data('invoice-number');
                const paymentMethod = $('#payment-method').val();

                self.processPayment(invoiceId, invoiceNumber, paymentMethod);
            });

            // Click outside to close
            $('#invoice-payment-modal').off('click').on('click', function(e) {
                if ($(e.target).is('#invoice-payment-modal')) {
                    $(this).hide();
                }
            });
        },

        /**
         * Process payment via AJAX
         *
         * @param {number} invoiceId Invoice ID
         * @param {string} invoiceNumber Invoice number
         * @param {string} paymentMethod Payment method
         */
        processPayment(invoiceId, invoiceNumber, paymentMethod) {
            const self = this;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'handle_invoice_payment',
                    invoice_id: invoiceId,
                    payment_method: paymentMethod,
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    $('.modal-confirm').prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('success', response.data.message || 'Payment processed successfully');
                        $('#invoice-payment-modal').hide();

                        // Refresh invoice details and datatable
                        if (window.CompanyInvoice) {
                            window.CompanyInvoice.loadInvoiceDetails(invoiceId);
                            window.CompanyInvoice.refreshDataTable();
                        }
                    } else {
                        self.showToast('error', response.data.message || 'Payment failed');
                    }
                },
                error: function(xhr, status, error) {
                    self.showToast('error', 'Failed to process payment: ' + error);
                },
                complete: function() {
                    $('.modal-confirm').prop('disabled', false).text('Bayar Sekarang');
                }
            });
        },

        /**
         * Show cancel invoice confirmation
         * Now uses pre-rendered PHP template, just populates data
         *
         * @param {number} invoiceId Invoice ID
         * @param {string} invoiceNumber Invoice number
         */
        showCancelConfirmation(invoiceId, invoiceNumber) {
            // Populate modal with invoice data
            $('#cancel-invoice-number').text(invoiceNumber);

            // Set data attribute on confirm button
            $('#cancel-confirm-btn').attr('data-invoice-id', invoiceId);

            // Show modal
            $('#invoice-cancel-modal').show();

            // Bind events
            this.bindCancelModalEvents();
        },

        /**
         * Bind cancel modal event handlers
         */
        bindCancelModalEvents() {
            const self = this;

            // Close button
            $('.modal-close, .modal-cancel').off('click').on('click', () => {
                $('#invoice-cancel-modal').hide();
            });

            // Confirm button
            $('.modal-confirm').off('click').on('click', function(e) {
                const $button = $(this);
                const invoiceId = $button.data('invoice-id');

                self.cancelInvoice(invoiceId);
            });
        },

        /**
         * Cancel invoice via AJAX
         *
         * @param {number} invoiceId Invoice ID
         */
        cancelInvoice(invoiceId) {
            const self = this;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_company_invoice',
                    invoice_id: invoiceId,
                    status: 'cancelled',
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    $('.modal-confirm').prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('success', 'Invoice berhasil dibatalkan');
                        $('#invoice-cancel-modal').hide();

                        // Refresh invoice details and datatable
                        if (window.CompanyInvoice) {
                            window.CompanyInvoice.loadInvoiceDetails(invoiceId);
                            window.CompanyInvoice.refreshDataTable();
                        }
                    } else {
                        self.showToast('error', response.data.message || 'Gagal membatalkan invoice');
                    }
                },
                error: function(xhr, status, error) {
                    self.showToast('error', 'Gagal membatalkan invoice: ' + error);
                },
                complete: function() {
                    $('.modal-confirm').prop('disabled', false).text('Ya, Batalkan');
                }
            });
        },

        /**
         * Format currency for display
         *
         * @param {number} amount Amount to format
         * @return {string} Formatted currency
         */
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        },

        /**
         * Show toast notification
         *
         * @param {string} type Toast type (success, error, info, warning)
         * @param {string} message Toast message
         */
        showToast(type, message) {
            // Use customer toast if available
            if (typeof CustomerToast !== 'undefined' && CustomerToast[type]) {
                CustomerToast[type](message);
            } else if (console) {
                console.log(`${type}: ${message}`);
            }
        }
    };

    // Expose to global scope
    window.InvoicePaymentModal = InvoicePaymentModal;

})(jQuery);
