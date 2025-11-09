/**
 * Customer Employees DataTable Initialization
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /assets/js/customer/employees-datatable.js
 *
 * Description: Minimal DataTable initialization for employees tab.
 *              Delegates all interactions to wp-datatable framework.
 *              Auto-initializes when employees tab becomes visible.
 *
 * Dependencies:
 * - jQuery
 * - DataTables (loaded by wp-datatable BaseAssets)
 * - wp-datatable tab-manager.js (handles tab switching)
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2194)
 * - Initial implementation
 * - Minimal code following branches-datatable.js pattern
 * - Tab switching handled by wp-datatable framework
 * - Initialize on wpdt:tab-switched event
 */

(function($) {
    'use strict';

    // Configuration from wp_localize_script
    const nonce = wpCustomerConfig.nonce;
    const ajaxurl = wpCustomerConfig.ajaxUrl;

    let employeesTable = null;

    /**
     * Initialize Employees DataTable
     */
    function initEmployeesDataTable() {
        const $table = $('#employees-datatable');

        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        const customerId = $table.data('customer-id');

        if (!customerId) {
            console.error('[Employees DataTable] customer-id not found');
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
                    d.action = 'get_customer_employees_datatable';
                    d.nonce = nonce;
                    d.customer_id = customerId;
                }
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'position', name: 'position' },
                { data: 'department', name: 'department', orderable: false, searchable: false },
                { data: 'email', name: 'email' },
                { data: 'branch_name', name: 'branch_name' },
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

        console.log('[Employees DataTable] Initialized for customer:', customerId);
    }

    /**
     * Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        // Initialize DataTable when employees tab becomes active
        if (data.tabId === 'employees') {
            console.log('[Employees DataTable] Tab switched to employees');

            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initEmployeesDataTable();
            }, 100);
        }
    });

    /**
     * Document ready - check if employees tab is already active
     */
    $(document).ready(function() {
        // Check if employees tab is active on page load (e.g., from hash)
        const $employeesTab = $('.nav-tab[data-tab="employees"]');

        if ($employeesTab.hasClass('nav-tab-active')) {
            console.log('[Employees DataTable] Employees tab active on load');
            setTimeout(function() {
                initEmployeesDataTable();
            }, 100);
        }
    });

})(jQuery);
