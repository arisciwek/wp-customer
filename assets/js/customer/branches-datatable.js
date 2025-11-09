/**
 * Customer Branches DataTable Initialization
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /assets/js/customer/branches-datatable.js
 *
 * Description: Minimal DataTable initialization for branches tab.
 *              Delegates all interactions to wp-datatable framework.
 *              Auto-initializes when branches tab becomes visible.
 *
 * Dependencies:
 * - jQuery
 * - DataTables (loaded by wp-datatable BaseAssets)
 * - wp-datatable tab-manager.js (handles tab switching)
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2193)
 * - Initial implementation
 * - Minimal code following customer-datatable.js pattern
 * - Tab switching handled by wp-datatable framework
 * - Initialize on wpdt:tab-switched event
 */

(function($) {
    'use strict';

    // Configuration from wp_localize_script
    const nonce = wpCustomerConfig.nonce;
    const ajaxurl = wpCustomerConfig.ajaxUrl;

    let branchesTable = null;

    /**
     * Initialize Branches DataTable
     */
    function initBranchesDataTable() {
        const $table = $('#branches-datatable');

        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        const customerId = $table.data('customer-id');

        if (!customerId) {
            console.error('[Branches DataTable] customer-id not found');
            return;
        }

        // Initialize DataTable with minimal config
        branchesTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_customer_branches_datatable';
                    d.nonce = nonce;
                    d.customer_id = customerId;
                }
            },
            columns: [
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'type', name: 'type' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                { data: 'status', name: 'status' },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[1, 'asc']], // Sort by name
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
                emptyTable: 'Tidak ada data cabang',
                zeroRecords: 'Tidak ada data yang cocok'
            }
        });

        console.log('[Branches DataTable] Initialized for customer:', customerId);
    }

    /**
     * Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        // Initialize DataTable when branches tab becomes active
        if (data.tabId === 'branches') {
            console.log('[Branches DataTable] Tab switched to branches');

            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initBranchesDataTable();
            }, 100);
        }
    });

    /**
     * Document ready - check if branches tab is already active
     */
    $(document).ready(function() {
        // Check if branches tab is active on page load (e.g., from hash)
        const $branchesTab = $('.nav-tab[data-tab="branches"]');

        if ($branchesTab.hasClass('nav-tab-active')) {
            console.log('[Branches DataTable] Branches tab active on load');
            setTimeout(function() {
                initBranchesDataTable();
            }, 100);
        }
    });

})(jQuery);
