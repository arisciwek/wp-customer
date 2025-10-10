/**
 * Company Invoice Payment Modal
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-invoice-payment-modal.js
 *
 * Description: Payment modal handler untuk invoice payment.
 *              Extracted dari company-membership.js dan adapted
 *              untuk invoice payment flow.
 *              Menangani tampilan modal, validasi input, dan
 *              submit pembayaran via AJAX.
 *
 * Dependencies:
 * - jQuery
 * - WordPress AJAX
 * - wpCustomerData (nonce, ajaxUrl)
 *
 * Changelog:
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
         *
         * @param {number} invoiceId Invoice ID
         * @param {string} invoiceNumber Invoice number
         * @param {number} amount Invoice amount
         */
        showPaymentModal(invoiceId, invoiceNumber, amount) {
            const modalHtml = `
                <div class="wp-customer-modal" id="invoice-payment-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">Pembayaran Invoice</h3>
                            <button type="button" class="modal-close dashicons dashicons-no-alt"></button>
                        </div>

                        <div class="modal-body">
                            <p>Pembayaran untuk invoice <strong>${invoiceNumber}</strong></p>
                            <p>Total: <strong>Rp ${this.formatCurrency(amount)}</strong></p>

                            <div class="payment-details">
                                <div class="form-row">
                                    <label for="payment-method">Metode Pembayaran</label>
                                    <select id="payment-method" name="payment_method">
                                        <option value="transfer_bank">Transfer Bank</option>
                                        <option value="virtual_account">Virtual Account</option>
                                        <option value="kartu_kredit">Kartu Kredit</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                </div>
                            </div>

                            <div class="confirmation-notice">
                                <p>Dengan melanjutkan, Anda setuju untuk melakukan pembayaran invoice ini.</p>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="button modal-cancel">Batal</button>
                            <button type="button" class="button button-primary modal-confirm"
                                    data-invoice-id="${invoiceId}"
                                    data-invoice-number="${invoiceNumber}"
                                    data-amount="${amount}">
                                Bayar Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body if not exists
            if ($('#invoice-payment-modal').length === 0) {
                $('body').append(modalHtml);
            } else {
                $('#invoice-payment-modal').replaceWith(modalHtml);
            }

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
         *
         * @param {number} invoiceId Invoice ID
         * @param {string} invoiceNumber Invoice number
         */
        showCancelConfirmation(invoiceId, invoiceNumber) {
            const modalHtml = `
                <div class="wp-customer-modal" id="invoice-cancel-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">Batalkan Invoice</h3>
                            <button type="button" class="modal-close dashicons dashicons-no-alt"></button>
                        </div>

                        <div class="modal-body">
                            <p>Apakah Anda yakin ingin membatalkan invoice <strong>${invoiceNumber}</strong>?</p>
                            <div class="confirmation-notice" style="color: #d63638;">
                                <p>Invoice yang dibatalkan tidak dapat dikembalikan.</p>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="button modal-cancel">Batal</button>
                            <button type="button" class="button button-primary modal-confirm"
                                    data-invoice-id="${invoiceId}"
                                    style="background-color: #d63638; border-color: #d63638;">
                                Ya, Batalkan
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            if ($('#invoice-cancel-modal').length === 0) {
                $('body').append(modalHtml);
            } else {
                $('#invoice-cancel-modal').replaceWith(modalHtml);
            }

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
