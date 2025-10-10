/**
 * Company Invoice DataTable Configuration
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-invoice-datatable-script.js
 *
 * Description: DataTable configuration untuk company invoice listing.
 *              Separated dari main script untuk better organization.
 *              Handles server-side processing, columns, dan pagination.
 *
 * Dependencies:
 * - jQuery
 * - DataTables
 * - WordPress AJAX
 * - CompanyInvoice object (dari company-invoice-script.js)
 *
 * Changelog:
 * 1.0.0 - 2025-01-10
 * - Initial version (separated from company-invoice-script.js)
 * - Added DataTable configuration
 * - Added column definitions
 * - Added language localization
 */

(function($) {
    'use strict';

    const CompanyInvoiceDataTable = {
        initDataTable() {
            const self = window.CompanyInvoice || this;

            const dataTable = $('#company-invoices-table').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: function(d) {
                        return $.extend({}, d, {
                            action: 'handle_company_invoice_datatable',
                            nonce: wpCustomerData.nonce
                        });
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable error:', error, thrown);
                        if (self.showToast) {
                            self.showToast('error', 'Gagal memuat data invoice');
                        }
                    }
                },
                columns: [
                    {
                        data: 'invoice_number',
                        title: 'Nomor Invoice',
                        orderable: true
                    },
                    {
                        data: 'company_name',
                        title: 'Cabang',
                        orderable: true
                    },
                    {
                        data: 'amount',
                        title: 'Jumlah',
                        orderable: true
                    },
                    {
                        data: 'status',
                        title: 'Status',
                        orderable: true
                    },
                    {
                        data: 'due_date',
                        title: 'Jatuh Tempo',
                        orderable: true
                    },
                    {
                        data: 'created_at',
                        title: 'Tanggal',
                        orderable: true
                    },
                    {
                        data: null,
                        title: 'Aksi',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<button class="button button-small view-company-invoice" data-id="${row.id}">View</button>`;
                        }
                    }
                ],
                pageLength: 10,
                order: [[5, 'desc']], // Default sort by created date
                language: {
                    processing: 'Memuat...',
                    emptyTable: 'Tidak ada data invoice',
                    zeroRecords: 'Tidak ada invoice yang cocok',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ hingga _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 hingga 0 dari 0 data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                },
                drawCallback: function() {
                    if (self.bindActionButtons) {
                        self.bindActionButtons();
                    }
                }
            });

            return dataTable;
        }
    };

    // Expose to global scope
    window.CompanyInvoiceDataTable = CompanyInvoiceDataTable;

})(jQuery);
