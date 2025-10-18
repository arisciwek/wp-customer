/**
 * Company Invoice Management Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.3
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-invoice-script.js
 *
 * Description: Main JavaScript handler untuk halaman Company Invoice.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Dependencies:
 * - jQuery
 * - DataTables
 * - WordPress AJAX
 * - Custom toast notifications
 *
 * Changelog:
 * 1.0.3 - 2025-10-18 (Task-2162 Review-02)
 * - Fixed: Added "Lihat Bukti Pembayaran" button for 'pending_payment' status
 * - User can now view uploaded payment proof even before validation
 * - Issue: Button was missing for users after uploading payment proof
 * - Button now shows for both 'pending_payment' and 'paid' status
 *
 * 1.0.2 - 2025-10-18 (Debug Support)
 * - Added console log when "Bayar Invoice" button clicked (line 111)
 * - Logs invoice ID and number for debugging sequential payments
 * - Purpose: Verify invoice ID when user opens payment modal
 *
 * 1.0.1 - 2025-10-10
 * - Added DataTable initialization error handling
 * - Added console warnings for failed initialization
 * - Added module availability check
 * - Improved defensive programming
 *
 * 1.0.0 - 2024-12-25
 * - Initial version
 * - Added DataTable integration
 * - Added panel navigation
 * - Added AJAX handlers
 * - Added loading states
 * - Added error handling
 */

(function($) {
    'use strict';

    const CompanyInvoice = {
        currentId: null,
        isLoading: false,
        components: {
            container: null,
            rightPanel: null,
            dataTable: null,
            stats: {
                totalInvoices: null,
                pendingInvoices: null,
                paidInvoices: null,
                totalPaidAmount: null
            }
        },

        init() {
            this.components = {
                container: $('.wp-company-invoice-container'),
                rightPanel: $('.wp-company-invoice-right-panel'),
                stats: {
                    totalInvoices: $('#total-invoices'),
                    pendingInvoices: $('#pending-invoices'),
                    paidInvoices: $('#paid-invoices'),
                    totalPaidAmount: $('#total-paid-amount')
                }
            };

            this.initDataTable();
            this.bindEvents();
            this.loadStats();
        },

        initDataTable() {
            // Use external DataTable configuration
            if (window.CompanyInvoiceDataTable && window.CompanyInvoiceDataTable.initDataTable) {
                this.components.dataTable = window.CompanyInvoiceDataTable.initDataTable();

                if (!this.components.dataTable) {
                    console.warn('Company Invoice DataTable initialization returned null. Table may not be available on this page.');
                }
            } else {
                console.error('CompanyInvoiceDataTable module not loaded. Check script dependencies.');
            }
        },

        bindEvents() {
            const self = this;

            // Close panel button
            $(document).on('click', '.wp-company-invoice-close-panel', function() {
                self.closeRightPanel();
            });

            // Tab navigation in right panel
            $(document).on('click', '.nav-tab', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                if (tabId) {
                    self.switchTab(tabId);
                }
            });

            // Payment modal buttons
            $(document).on('click', '.btn-pay-invoice', function(e) {
                e.preventDefault();
                const invoiceId = $(this).data('id');
                const invoiceNumber = $(this).data('number');
                const amount = $(this).data('amount');

                console.log('[DEBUG] Bayar Invoice button clicked - Invoice ID:', invoiceId, 'Invoice Number:', invoiceNumber);

                if (window.InvoicePaymentModal) {
                    window.InvoicePaymentModal.showPaymentModal(invoiceId, invoiceNumber, amount);
                }
            });

            $(document).on('click', '.btn-cancel-invoice', function(e) {
                e.preventDefault();
                const invoiceId = $(this).data('id');
                const invoiceNumber = $('#invoice-number').text();

                if (window.InvoicePaymentModal) {
                    window.InvoicePaymentModal.showCancelConfirmation(invoiceId, invoiceNumber);
                }
            });

            $(document).on('click', '.btn-view-payment', function(e) {
                e.preventDefault();
                const invoiceId = $(this).data('id');
                // Show payment proof modal instead of payment info tab
                if (window.PaymentProofModal) {
                    window.PaymentProofModal.showModal(invoiceId);
                } else {
                    // Fallback to payment info tab if modal not available
                    self.viewPaymentInfo(invoiceId);
                }
            });

            // Outside click to close panel
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.wp-company-invoice-container').length) {
                    self.closeRightPanel();
                }
            });
        },

        bindActionButtons() {
            const self = this;

            // View company invoice details
            $('.view-company-invoice').off('click').on('click', function() {
                const invoiceId = $(this).data('id');
                self.viewInvoiceDetails(invoiceId);
            });
        },

        viewInvoiceDetails(invoiceId) {
            this.currentId = invoiceId;
            this.showRightPanel();

            // Load invoice details
            this.loadInvoiceDetails(invoiceId);
        },

        viewPaymentInfo(invoiceId) {
            this.currentId = invoiceId;
            this.showRightPanel();

            // Switch to payment tab and load data
            this.switchTab('payment-info');
            this.loadPaymentInfo(invoiceId);
        },

        loadInvoiceDetails(invoiceId) {
            const self = this;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_invoice_details',
                    id: invoiceId,
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    self.showLoading('invoice-details');
                },
                success: function(response) {
                    if (response.success) {
                        self.renderInvoiceDetails(response.data);
                    } else {
                        self.showToast('error', response.data.message || 'Failed to load invoice details');
                    }
                },
                error: function() {
                    self.showToast('error', 'Failed to load invoice details');
                },
                complete: function() {
                    self.hideLoading('invoice-details');
                }
            });
        },

        loadPaymentInfo(invoiceId) {
            const self = this;

            console.log('[DEBUG Review-03 JS] Loading payment info for invoice:', invoiceId);

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_invoice_payments',
                    id: invoiceId,
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    console.log('[DEBUG Review-03 JS] AJAX request started');
                    self.showLoading('payment-info');
                },
                success: function(response) {
                    console.log('[DEBUG Review-03 JS] AJAX response received:', response);
                    console.log('[DEBUG Review-03 JS] Response.success:', response.success);
                    console.log('[DEBUG Review-03 JS] Response.data:', response.data);

                    if (response.success) {
                        console.log('[DEBUG Review-03 JS] Payments array:', response.data.payments);
                        console.log('[DEBUG Review-03 JS] Payments count:', response.data.payments ? response.data.payments.length : 0);
                        self.renderPaymentInfo(response.data);
                    } else {
                        console.error('[DEBUG Review-03 JS] Error in response:', response.data.message);
                        self.showToast('error', response.data.message || 'Failed to load payment info');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[DEBUG Review-03 JS] AJAX error:', {xhr, status, error});
                    self.showToast('error', 'Failed to load payment info');
                },
                complete: function() {
                    console.log('[DEBUG Review-03 JS] AJAX request completed');
                    self.hideLoading('payment-info');
                }
            });
        },

        renderInvoiceDetails(data) {
            // Update header
            $('#invoice-header-number').text(data.invoice_number || '-');

            // Update invoice details using existing template structure
            $('#invoice-number').text(data.invoice_number || '-');
            $('#invoice-customer').text(data.customer_name || '-');
            $('#invoice-branch').text(data.branch_name || '-');
            $('#invoice-amount').text('Rp ' + this.formatCurrency(data.amount));

            // Update status with badge
            const statusBadge = this.getStatusBadge(data.status);
            $('#invoice-status').html(statusBadge).removeClass().addClass(this.getStatusClass(data.status));

            $('#invoice-due-date').text(this.formatDate(data.due_date));
            $('#invoice-created-at').text(this.formatDate(data.created_at));
            $('#invoice-created-by').text(data.created_by_name || '-');

            // Render action buttons based on status and payment permission
            this.renderActionButtons(data.status, data.id, data.invoice_number, data.amount, data.can_pay);

            // Show invoice details tab (pass false to prevent re-loading)
            this.switchTab('invoice-details', false);
        },

        renderPaymentInfo(data) {
            console.log('[DEBUG Review-03 JS] renderPaymentInfo called with data:', data);

            // Clear existing content first
            $('#payment-history-table tbody').empty();
            $('#payment-details').empty();

            if (data.payments && data.payments.length > 0) {
                console.log('[DEBUG Review-03 JS] Rendering', data.payments.length, 'payment records');

                // Populate payment history table
                let tableRows = '';
                data.payments.forEach((payment, index) => {
                    console.log(`[DEBUG Review-03 JS] Payment ${index}:`, payment);
                    console.log(`[DEBUG Review-03 JS] - amount:`, payment.amount);
                    console.log(`[DEBUG Review-03 JS] - payment_date:`, payment.payment_date);
                    console.log(`[DEBUG Review-03 JS] - notes:`, payment.notes);

                    tableRows += `
                        <tr>
                            <td>${this.formatDate(payment.payment_date)}</td>
                            <td>Rp ${this.formatCurrency(payment.amount)}</td>
                            <td>${this.getPaymentMethodLabel(payment.payment_method)}</td>
                            <td>${this.getPaymentStatusBadge(payment.status)}</td>
                            <td>${payment.notes || '-'}</td>
                        </tr>
                    `;
                });

                $('#payment-history-table tbody').html(tableRows);

                // Show summary in payment details
                const totalAmount = data.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
                const summaryHtml = `
                    <div class="payment-summary">
                        <p><strong>Total Pembayaran:</strong> Rp ${this.formatCurrency(totalAmount)}</p>
                        <p><strong>Jumlah Transaksi:</strong> ${data.payments.length}</p>
                    </div>
                `;
                $('#payment-details').html(summaryHtml);

                console.log('[DEBUG Review-03 JS] Table rows rendered');
            } else {
                console.log('[DEBUG Review-03 JS] No payments found, showing empty state');
                $('#payment-history-table tbody').html('<tr><td colspan="5" class="text-center">Belum ada pembayaran untuk invoice ini</td></tr>');
                $('#payment-details').html('<p class="no-payments">Belum ada pembayaran untuk invoice ini.</p>');
            }

            console.log('[DEBUG Review-03 JS] Payment info rendered successfully');
        },

        getPaymentMethodLabel(method) {
            const labels = {
                'transfer_bank': 'Transfer Bank',
                'virtual_account': 'Virtual Account',
                'kartu_kredit': 'Kartu Kredit',
                'e_wallet': 'E-Wallet'
            };
            return labels[method] || method;
        },

        getPaymentStatusBadge(status) {
            const badges = {
                'completed': '<span class="badge badge-success">Completed</span>',
                'pending': '<span class="badge badge-warning">Pending</span>',
                'failed': '<span class="badge badge-danger">Failed</span>'
            };
            return badges[status] || status;
        },

        loadStats() {
            const self = this;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_invoice_stats',
                    nonce: wpCustomerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStats(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load stats');
                }
            });
        },

        updateStats(data) {
            if (this.components.stats.totalInvoices) {
                this.components.stats.totalInvoices.text(data.total_invoices || 0);
            }
            if (this.components.stats.pendingInvoices) {
                this.components.stats.pendingInvoices.text(data.pending_invoices || 0);
            }
            if (this.components.stats.paidInvoices) {
                this.components.stats.paidInvoices.text(data.paid_invoices || 0);
            }
            if (this.components.stats.totalPaidAmount) {
                this.components.stats.totalPaidAmount.text('Rp ' + this.formatCurrency(data.total_paid_amount || 0));
            }
        },

        showRightPanel() {
            this.components.rightPanel.removeClass('hidden').addClass('visible');
            this.components.container.addClass('with-right-panel');
        },

        closeRightPanel() {
            this.components.rightPanel.removeClass('visible').addClass('hidden');
            this.components.container.removeClass('with-right-panel');
            this.currentId = null;
        },

        switchTab(tabId, shouldLoadData = true) {
            // Update tab navigation
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

            // Update tab content
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');

            // Load data only if explicitly requested (e.g., when user clicks tab, not when rendering)
            if (shouldLoadData && this.currentId) {
                if (tabId === 'invoice-details') {
                    this.loadInvoiceDetails(this.currentId);
                } else if (tabId === 'payment-info') {
                    this.loadPaymentInfo(this.currentId);
                }
            }
        },

        showLoading(section) {
            $(`#${section}-content`).html('<div class="invoice-loading"><div class="invoice-loading-spinner"></div>Loading...</div>');
        },

        hideLoading(section) {
            // Loading is hidden when content is replaced
        },

        getStatusBadge(status) {
            const badges = {
                'pending': '<span class="invoice-status-badge invoice-status-pending">Belum Dibayar</span>',
                'pending_payment': '<span class="invoice-status-badge invoice-status-pending-payment">Menunggu Validasi</span>',
                'paid': '<span class="invoice-status-badge invoice-status-paid">Lunas</span>',
                'cancelled': '<span class="invoice-status-badge invoice-status-cancelled">Dibatalkan</span>'
            };
            return badges[status] || status;
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'status-badge status-pending',
                'pending_payment': 'status-badge status-pending-payment',
                'paid': 'status-badge status-paid',
                'cancelled': 'status-badge status-cancelled'
            };
            return classes[status] || 'status-badge';
        },

        getActionButtons(data) {
            return `
                <button class="invoice-action-btn view-details" data-id="${data.id}" title="View Details">
                    View
                </button>
                <button class="invoice-action-btn view-payment" data-id="${data.id}" title="View Payment">
                    Payment
                </button>
            `;
        },

        renderActionButtons(status, invoiceId, invoiceNumber, amount, canPay) {
            let buttons = '';

            if (status === 'pending') {
                // Pending: show payment button
                if (canPay) {
                    buttons = `
                        <p class="description">Status: Belum Dibayar</p>
                        <button class="button button-primary btn-pay-invoice"
                                data-id="${invoiceId}"
                                data-number="${invoiceNumber}"
                                data-amount="${amount}">
                            Bayar Invoice
                        </button>
                        <p class="description" style="font-size: 11px; color: #666; margin-top: 10px;">
                            <em>* Upload bukti pembayaran akan tersedia segera</em>
                        </p>
                    `;
                } else {
                    buttons = '<p class="description">Status: Belum Dibayar</p>';
                }
            } else if (status === 'pending_payment') {
                // Pending payment: uploaded proof, waiting for validation
                buttons = `
                    <p class="description" style="color: #d4a42b; font-weight: 600;">
                        ⏳ Menunggu Validasi Pembayaran
                    </p>
                    <p class="description" style="font-size: 12px; color: #666; margin-bottom: 10px;">
                        Bukti pembayaran sudah diupload, menunggu verifikasi
                    </p>
                    <button class="button btn-view-payment"
                            data-id="${invoiceId}">
                        Lihat Bukti Pembayaran
                    </button>
                `;
            } else if (status === 'paid') {
                buttons = `
                    <button class="button button-primary btn-view-payment"
                            data-id="${invoiceId}">
                        Lihat Bukti Pembayaran
                    </button>
                `;
            } else if (status === 'cancelled') {
                buttons = '<p class="description">Invoice telah dibatalkan</p>';
            }

            $('#invoice-actions-buttons').html(buttons);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        showToast(type, message) {
            // Use customer toast if available
            if (typeof CustomerToast !== 'undefined' && CustomerToast[type]) {
                CustomerToast[type](message);
            } else if (console) {
                console.log(`${type}: ${message}`);
            }
        },

        refreshDataTable() {
            if (this.components.dataTable) {
                this.components.dataTable.ajax.reload();
            }
            this.loadStats();
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if (typeof wpCustomerData !== 'undefined') {
            window.CompanyInvoice = CompanyInvoice;
            CompanyInvoice.init();
        }
    });

})(jQuery);
