/**
 * Company Invoice Payment Proof Modal Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-invoice-payment-proof.js
 *
 * Description: Handler untuk modal payment proof.
 *              Menampilkan detail pembayaran dan bukti pembayaran.
 *              Includes image preview dan download button (placeholder).
 *
 * Dependencies:
 * - jQuery
 * - WordPress AJAX
 * - CompanyInvoice object (dari company-invoice-script.js)
 *
 * Changelog:
 * 1.0.0 - 2025-10-18
 * - Initial version
 * - Added modal show/hide functionality
 * - Added payment proof loading
 * - Added image preview
 * - Added download button placeholder
 */

(function($) {
    'use strict';

    const PaymentProofModal = {
        $modal: null,
        $overlay: null,
        currentInvoiceId: null,
        currentPaymentData: null,

        init() {
            this.$modal = $('#payment-proof-modal');
            this.$overlay = this.$modal.find('.wp-customer-modal-overlay');
            this.bindEvents();
        },

        bindEvents() {
            const self = this;

            // Close modal events
            $('#close-payment-proof-modal, #cancel-payment-proof-modal').on('click', function(e) {
                e.preventDefault();
                self.closeModal();
            });

            this.$overlay.on('click', function() {
                self.closeModal();
            });

            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal.is(':visible')) {
                    self.closeModal();
                }
            });

            // Download button (placeholder)
            $('#download-payment-proof').on('click', function(e) {
                e.preventDefault();
                self.downloadProof();
            });
        },

        showModal(invoiceId) {
            this.currentInvoiceId = invoiceId;
            this.$modal.fadeIn(300);
            $('body').addClass('modal-open');

            // Load payment proof data
            this.loadPaymentProof(invoiceId);
        },

        closeModal() {
            this.$modal.fadeOut(300);
            $('body').removeClass('modal-open');
            this.currentInvoiceId = null;
            this.currentPaymentData = null;

            // Reset preview
            $('#proof-file-preview').html(`
                <div class="proof-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <p>Memuat bukti pembayaran...</p>
                </div>
            `);
        },

        loadPaymentProof(invoiceId) {
            const self = this;

            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_invoice_payment_proof',
                    invoice_id: invoiceId,
                    nonce: wpCustomerData.nonce
                },
                beforeSend: function() {
                    self.showLoading();
                },
                success: function(response) {
                    if (response.success) {
                        self.currentPaymentData = response.data;
                        self.renderPaymentProof(response.data);
                    } else {
                        self.showError(response.data.message || 'Gagal memuat bukti pembayaran');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading payment proof:', error);
                    self.showError('Terjadi kesalahan saat memuat bukti pembayaran');
                }
            });
        },

        renderPaymentProof(data) {
            // Populate payment info
            $('#proof-invoice-number').text(data.invoice_number || '-');
            $('#proof-payment-date').text(this.formatDate(data.payment_date));
            $('#proof-payment-amount').text('Rp ' + this.formatCurrency(data.amount));
            $('#proof-payment-method').text(this.getPaymentMethodLabel(data.payment_method));
            $('#proof-payment-notes').text(data.notes || '-');

            // Set status badge
            const statusHtml = this.getPaymentStatusBadge(data.status);
            $('#proof-payment-status').html(statusHtml);

            // Render proof file preview
            this.renderProofPreview(data.proof_file_url, data.proof_file_type);
        },

        renderProofPreview(fileUrl, fileType) {
            const $preview = $('#proof-file-preview');

            if (!fileUrl) {
                $preview.html(`
                    <div class="proof-no-file">
                        <span class="dashicons dashicons-media-default"></span>
                        <p>Tidak ada bukti pembayaran yang diunggah</p>
                    </div>
                `);
                return;
            }

            // Determine file type and render accordingly
            if (fileType && fileType.startsWith('image/')) {
                // Image file
                $preview.html(`
                    <img src="${fileUrl}" alt="Bukti Pembayaran" />
                `);
            } else if (fileType === 'application/pdf') {
                // PDF file
                $preview.html(`
                    <div class="proof-pdf-preview">
                        <span class="dashicons dashicons-pdf"></span>
                        <p>File PDF</p>
                        <a href="${fileUrl}" target="_blank" class="button">Buka PDF</a>
                    </div>
                `);
            } else {
                // Other file types
                $preview.html(`
                    <div class="proof-file-preview">
                        <span class="dashicons dashicons-media-document"></span>
                        <p>File: ${this.getFileName(fileUrl)}</p>
                        <a href="${fileUrl}" target="_blank" class="button">Lihat File</a>
                    </div>
                `);
            }
        },

        showLoading() {
            $('#proof-file-preview').html(`
                <div class="proof-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <p>Memuat bukti pembayaran...</p>
                </div>
            `);
        },

        showError(message) {
            $('#proof-file-preview').html(`
                <div class="proof-error">
                    <span class="dashicons dashicons-warning"></span>
                    <p>${message}</p>
                </div>
            `);
        },

        downloadProof() {
            // Placeholder for download functionality
            alert('Fitur download akan segera tersedia');
            // TODO: Implement download functionality in next task
        },

        // Helper methods
        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        },

        formatCurrency(amount) {
            if (!amount) return '0';
            return parseFloat(amount).toLocaleString('id-ID');
        },

        getPaymentMethodLabel(method) {
            const methods = {
                'transfer_bank': 'Transfer Bank',
                'virtual_account': 'Virtual Account',
                'kartu_kredit': 'Kartu Kredit',
                'e_wallet': 'E-Wallet',
                'cash': 'Tunai'
            };
            return methods[method] || method || '-';
        },

        getPaymentStatusBadge(status) {
            const badges = {
                'paid': '<span class="status-badge paid">Lunas</span>',
                'pending': '<span class="status-badge pending">Menunggu</span>',
                'failed': '<span class="status-badge failed">Gagal</span>'
            };
            return badges[status] || '<span class="status-badge">' + status + '</span>';
        },

        getFileName(url) {
            if (!url) return '';
            const parts = url.split('/');
            return parts[parts.length - 1];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        PaymentProofModal.init();

        // Expose to global scope for external access
        window.PaymentProofModal = PaymentProofModal;
    });

})(jQuery);
