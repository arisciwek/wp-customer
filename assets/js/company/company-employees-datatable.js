/**
 * Company Employees DataTable Initialization
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /assets/js/company/company-employees-datatable.js
 *
 * Description: Minimal DataTable initialization for company staff tab.
 *              Delegates all interactions to wp-datatable framework.
 *              Auto-initializes when staff tab becomes visible.
 *
 * Dependencies:
 * - jQuery
 * - DataTables (loaded by wp-datatable BaseAssets)
 * - wp-datatable tab-manager.js (handles tab switching)
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation following employees-datatable.js pattern
 * - Minimal code for company staff tab
 * - Tab switching handled by wp-datatable framework
 * - Initialize on wpdt:tab-switched event
 */

(function($) {
    'use strict';

    // Configuration from wp_localize_script
    const nonce = wpCompanyConfig.nonce;
    const ajaxurl = wpCompanyConfig.ajaxUrl;

    let employeesTable = null;

    /**
     * Initialize Company Employees DataTable
     */
    function initCompanyEmployeesDataTable() {
        const $table = $('#company-employees-datatable');

        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        const companyId = $table.data('company-id');

        if (!companyId) {
            console.error('[Company Employees DataTable] company-id not found');
            return;
        }

        // Initialize DataTable with minimal config
        employeesTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_company_employees_datatable';
                    d.nonce = nonce;
                    d.company_id = companyId;
                }
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'position', name: 'position' },
                { data: 'department', name: 'department', orderable: false, searchable: false },
                { data: 'email', name: 'email' },
                { data: 'status', name: 'status' },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[0, 'asc']], // Sort by name
            pageLength: 10,
            language: {
                processing: 'Memuat data...',
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                },
                emptyTable: 'Tidak ada data karyawan',
                zeroRecords: 'Tidak ada data yang cocok'
            }
        });

        console.log('[Company Employees DataTable] Initialized for company:', companyId);
    }

    /**
     * Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        // Initialize DataTable when staff tab becomes active
        if (data.tabId === 'staff') {
            console.log('[Company Employees DataTable] Tab switched to staff');

            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initCompanyEmployeesDataTable();
            }, 100);
        }
    });

    /**
     * Document ready - check if staff tab is already active
     */
    $(document).ready(function() {
        // Check if staff tab is active on page load (e.g., from hash)
        const $staffTab = $('.nav-tab[data-tab="staff"]');

        if ($staffTab.hasClass('nav-tab-active')) {
            console.log('[Company Employees DataTable] Staff tab active on load');
            setTimeout(function() {
                initCompanyEmployeesDataTable();
            }, 100);
        }
    });

})(jQuery);
