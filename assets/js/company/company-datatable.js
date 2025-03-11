/**
 * Company DataTable Handler
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables company.
 *              Menangani server-side processing dan panel kanan.
 *              Terintegrasi dengan Company main script.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - CustomerToast for notifications
 */

(function($) {
    'use strict';

    const CompanyDataTable = {
        table: null,
        initialized: false,
        currentHighlight: null,

        init() {
            if (this.initialized) {
                return;
            }

            // Wait for dependencies
            if (!window.Company || !window.CustomerToast) {
                setTimeout(() => this.init(), 100);
                return;
            }

            this.initialized = true;
            this.initDataTable();
            this.bindEvents();
            this.handleInitialHash();
        },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#companies-table')) {
                $('#companies-table').DataTable().destroy();
            }

            // Initialize clean table structure
            $('#companies-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Perusahaan</th>
                        <th>Tipe</th>
                        <th>Level</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            this.table = $('#companies-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpCustomerData.ajaxUrl,
                    type: 'POST',
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_company_datatable',
                            nonce: wpCustomerData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        CustomerToast.error('Gagal memuat data perusahaan');
                    }
                },
                columns: [
                    {
                        data: 'code',
                        title: 'Kode',
                        width: '100px'
                    },
                    {
                        data: 'name',
                        title: 'Nama Perusahaan'
                    },
                    {
                        data: 'type',
                        title: 'Tipe',
                        width: '80px'
                    },
                    {
                        data: 'level_name',
                        title: 'Level',
                        width: '100px',
                        defaultContent: '-'
                    },
                    {
                        data: 'actions',
                        title: 'Aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center nowrap',
                        width: '50px'
                    }
                ],
                order: [[0, 'asc']], // Default sort by code
                pageLength: wpCustomerData.perPage || 10,
                language: {
                    "emptyTable": "Tidak ada data yang tersedia",
                    "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "loadingRecords": "Memuat...",
                    "processing": "Memproses...",
                    "search": "Cari:",
                    "zeroRecords": "Tidak ditemukan data yang sesuai",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                drawCallback: (settings) => {
                    this.bindActionButtons();

                    // Get current hash if any
                    const hash = window.location.hash;
                    if (hash && hash.startsWith('#')) {
                        const id = hash.substring(1);
                        if (id) {
                            this.highlightRow(id);
                        }
                    }
                },
                createdRow: (row, data) => {
                    $(row).attr('data-id', data.id);
                }
            });
        },

        bindEvents() {
            // Hash change event
            $(window).off('hashchange.companyTable')
                    .on('hashchange.companyTable', () => this.handleHashChange());
        },

        bindActionButtons() {
            const $table = $('#companies-table');
            $table.off('click', '.view-company');

            // View action
            $table.on('click', '.view-company', (e) => {
                const id = $(e.currentTarget).data('id');
                if (id) window.location.hash = id;

                // Reset tab ke details
                $('.tab-content').removeClass('active');
                $('#company-details').addClass('active');
                $('.nav-tab').removeClass('nav-tab-active');
                $('.nav-tab[data-tab="company-details"]').addClass('nav-tab-active');
            });
        },

        handleHashChange() {
            const hash = window.location.hash;
            if (hash) {
                const id = hash.substring(1);
                if (id) {
                    this.highlightRow(id);
                }
            }
        },

        handleInitialHash() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#')) {
                this.handleHashChange();
            }
        },

        highlightRow(id) {
            if (this.currentHighlight) {
                $(`tr[data-id="${this.currentHighlight}"]`).removeClass('highlight');
            }

            const $row = $(`tr[data-id="${id}"]`);
            if ($row.length) {
                $row.addClass('highlight');
                this.currentHighlight = id;

                // Scroll into view if needed
                const container = this.table.table().container();
                const rowTop = $row.position().top;
                const containerHeight = $(container).height();
                const scrollTop = $(container).scrollTop();

                if (rowTop < scrollTop || rowTop > scrollTop + containerHeight) {
                    $row[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            }
        },

        refresh() {
            if (this.table) {
                this.table.ajax.reload(null, false);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CompanyDataTable = CompanyDataTable;
        CompanyDataTable.init();
    });

})(jQuery);
