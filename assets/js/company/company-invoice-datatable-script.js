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
 * 1.0.1 - 2025-10-10
 * - Added element existence check before DataTable initialization
 * - Added DataTables library availability check
 * - Added error handling with console warnings
 * - Prevents "Cannot read properties of undefined" error
 *
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
            const $table = $('#company-invoices-table');

            // Check if table element exists before initializing
            if ($table.length === 0) {
                console.warn('Company Invoice table element not found. Skipping DataTable initialization.');
                return null;
            }

            // Check if DataTable is available
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables library not loaded. Cannot initialize invoice table.');
                return null;
            }

            const dataTable = $table.DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: function(d) {
                        return $.extend({}, d, {
                            action: 'handle_company_invoice_datatable',
                            nonce: wpCustomerData.nonce,
                            filter_pending: $('#filter-pending').is(':checked') ? 1 : 0,
                            filter_pending_payment: $('#filter-pending-payment').is(':checked') ? 1 : 0,
                            filter_paid: $('#filter-paid').is(':checked') ? 1 : 0,
                            filter_cancelled: $('#filter-cancelled').is(':checked') ? 1 : 0
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
                        data: null,
                        title: 'Level',
                        orderable: false,
                        render: function(data, type, row) {
                            // Show upgrade indicator if from_level differs from target level
                            if (row.is_upgrade && row.from_level_name && row.level_name) {
                                return row.from_level_name + ' → ' + row.level_name + ' <span style="color: green; font-weight: bold;">⬆</span>';
                            }
                            // Show just the level name for renewal (same level)
                            return row.level_name || '-';
                        }
                    },
                    {
                        data: 'period_months',
                        title: 'Period',
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
                        data: null,
                        title: 'Aksi',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<button class="button button-small view-company-invoice" data-id="${row.id}">View</button>`;
                        }
                    }
                ],
                pageLength: 10,
                order: [[6, 'desc']], // Default sort by due date
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

            // Bind filter checkbox events
            $('#filter-pending, #filter-pending-payment, #filter-paid, #filter-cancelled').on('change', function() {
                console.log('Filter changed, reloading table...');
                dataTable.ajax.reload();
            });

            return dataTable;
        }
    };

    // Expose to global scope
    window.CompanyInvoiceDataTable = CompanyInvoiceDataTable;

})(jQuery);
