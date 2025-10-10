/**
 * Company Invoice Management Interface
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS
 * @version     1.0.0
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
                self.viewPaymentInfo(invoiceId);
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

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_company_invoice_payments',
                    id: invoiceId,
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    self.showLoading('payment-info');
                },
                success: function(response) {
                    if (response.success) {
                        self.renderPaymentInfo(response.data);
                    } else {
                        self.showToast('error', response.data.message || 'Failed to load payment info');
                    }
                },
                error: function() {
                    self.showToast('error', 'Failed to load payment info');
                },
                complete: function() {
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

            // Render action buttons based on status
            this.renderActionButtons(data.status, data.id, data.invoice_number, data.amount);

            // Show invoice details tab
            this.switchTab('invoice-details');
        },

        renderPaymentInfo(data) {
            let html = '';

            if (data.payments && data.payments.length > 0) {
                data.payments.forEach(payment => {
                    html += `
                        <div class="payment-record">
                            <div class="payment-amount">Rp ${this.formatCurrency(payment.amount)}</div>
                            <div class="payment-date">${this.formatDate(payment.payment_date)}</div>
                            ${payment.notes ? `<div class="payment-notes">${payment.notes}</div>` : ''}
                        </div>
                    `;
                });
            } else {
                html = '<p class="no-payments">No payments recorded for this invoice.</p>';
            }

            $('#payment-info-content').html(html);
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

        switchTab(tabId) {
            // Update tab navigation
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

            // Update tab content
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');

            // Load data if needed
            if (this.currentId) {
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
                'pending': '<span class="invoice-status-badge invoice-status-pending">Pending</span>',
                'paid': '<span class="invoice-status-badge invoice-status-paid">Paid</span>',
                'overdue': '<span class="invoice-status-badge invoice-status-overdue">Overdue</span>',
                'cancelled': '<span class="invoice-status-badge invoice-status-cancelled">Cancelled</span>'
            };
            return badges[status] || status;
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'status-badge status-pending',
                'paid': 'status-badge status-paid',
                'overdue': 'status-badge status-overdue',
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

        renderActionButtons(status, invoiceId, invoiceNumber, amount) {
            let buttons = '';

            if (status === 'pending' || status === 'overdue') {
                buttons = `
                    <button class="button button-primary btn-pay-invoice"
                            data-id="${invoiceId}"
                            data-number="${invoiceNumber}"
                            data-amount="${amount}"
                            style="margin-right: 10px;">
                        Bayar Sekarang
                    </button>
                    <button class="button btn-cancel-invoice"
                            data-id="${invoiceId}">
                        Batalkan Invoice
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
