/**
 * Company Invoice Payment Modal
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.1.3
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
 * 1.1.3 - 2025-10-18 (Critical Fix: jQuery Data Cache Issue)
 * - Fixed: Changed .attr() to .data() in showPaymentModal() (lines 76-79)
 * - Fixed: Changed .attr() to .data() in showCancelConfirmation() (line 185)
 * - Root Cause: jQuery .data() caches values - mixing .attr() set with .data() get causes stale data
 * - Bug: Sequential payments processed wrong invoice ID (always first invoice ID)
 * - Impact: Modal now correctly updates invoice data when opened for different invoices
 * - Critical: Prevents payment for wrong invoice
 *
 * 1.1.2 - 2025-10-18 (Debug Support)
 * - Added console log when "Bayar Sekarang" button clicked (line 99)
 * - Added console log when processPayment method called (line 122)
 * - Added console log in AJAX beforeSend (line 134)
 * - Logs invoice ID, number, and payment method for debugging
 * - Purpose: Track invoice ID through payment flow to ensure consistency
 *
 * 1.1.1 - 2025-10-18 (Task-2161 Sequential Payment UX Fix)
 * - Fixed: Auto-close right panel after successful payment (lines 128-134)
 * - Fixed: Auto-close right panel after successful cancel (lines 214-218)
 * - Changed: processPayment success calls closeRightPanel() instead of loadInvoiceDetails()
 * - Changed: cancelInvoice success calls closeRightPanel()
 * - Reason: Prevents accidental interaction with stale invoice state
 * - UX: Forces user to select invoice from list after payment, ensuring fresh data
 * - Benefit: Enables sequential payments for multiple invoices without confusion
 *
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

            // Set data on confirm button using .data() method
            // IMPORTANT: Use .data() instead of .attr() to avoid jQuery cache issues
            // .attr() sets HTML attribute, .data() sets jQuery internal data
            // If we mix .attr() for set and .data() for get, cached values won't update
            $('#payment-confirm-btn')
                .data('invoice-id', invoiceId)
                .data('invoice-number', invoiceNumber)
                .data('amount', amount);

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

                console.log('[DEBUG] Bayar Sekarang button clicked - Invoice ID:', invoiceId, 'Invoice Number:', invoiceNumber, 'Payment Method:', paymentMethod);

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

            console.log('[DEBUG] processPayment called - Invoice ID:', invoiceId, 'Invoice Number:', invoiceNumber, 'Payment Method:', paymentMethod);

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
                    console.log('[DEBUG] AJAX beforeSend - Sending invoice_id:', invoiceId);
                    $('.modal-confirm').prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('success', response.data.message || 'Payment processed successfully');
                        $('#invoice-payment-modal').hide();

                        // Close right panel and refresh datatable for cleaner UX
                        // This forces user to select invoice again from list,
                        // preventing accidental interaction with old invoice state
                        if (window.CompanyInvoice) {
                            window.CompanyInvoice.closeRightPanel();
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

            // Set data on confirm button using .data() method
            // IMPORTANT: Use .data() instead of .attr() to avoid jQuery cache issues
            $('#cancel-confirm-btn').data('invoice-id', invoiceId);

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

                        // Close right panel and refresh datatable for cleaner UX
                        if (window.CompanyInvoice) {
                            window.CompanyInvoice.closeRightPanel();
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
