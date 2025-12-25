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

    console.log('[Company Employees DataTable] Script loaded');
    console.log('[Company Employees DataTable] wpCompanyConfig:', wpCompanyConfig);

    // Configuration from wp_localize_script
    const nonce = wpCompanyConfig.nonce;
    const ajaxurl = wpCompanyConfig.ajaxUrl;

    let employeesTable = null;

    /**
     * Initialize Company Employees DataTable
     */
    function initCompanyEmployeesDataTable() {
        console.log('[Company Employees DataTable] initCompanyEmployeesDataTable called');

        const $table = $('#company-employees-datatable');
        console.log('[Company Employees DataTable] Table element found:', $table.length > 0);

        if (!$table.length) {
            console.error('[Company Employees DataTable] Table element not found');
            return;
        }

        if ($.fn.DataTable.isDataTable($table)) {
            console.log('[Company Employees DataTable] DataTable already initialized, skipping');
            return;
        }

        const companyId = $table.data('company-id');
        console.log('[Company Employees DataTable] Company ID:', companyId);

        if (!companyId) {
            console.error('[Company Employees DataTable] company-id not found on table element');
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
                    console.log('[Company Employees DataTable] AJAX data:', { action: d.action, company_id: companyId });
                },
                error: function(xhr, error, code) {
                    console.error('[Company Employees DataTable] AJAX error:', error, code);
                    console.error('[Company Employees DataTable] Response:', xhr.responseText);
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
     * Retry initialization with polling
     * Waits for table element to appear in DOM after AJAX load
     */
    function retryInitDataTable(maxRetries = 10, retryDelay = 200) {
        let retryCount = 0;

        const attemptInit = function() {
            const $table = $('#company-employees-datatable');

            if ($table.length > 0) {
                // Table found, initialize
                console.log('[Company Employees DataTable] Table element found after ' + retryCount + ' retries');
                if (!$.fn.DataTable.isDataTable($table)) {
                    initCompanyEmployeesDataTable();
                } else {
                    console.log('[Company Employees DataTable] DataTable already initialized');
                }
                return true;
            }

            // Table not found yet
            retryCount++;
            if (retryCount < maxRetries) {
                console.log('[Company Employees DataTable] Table not found, retry ' + retryCount + '/' + maxRetries);
                setTimeout(attemptInit, retryDelay);
            } else {
                console.error('[Company Employees DataTable] Max retries reached, table element not found');
            }
        };

        attemptInit();
    }

    /**
     * Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        console.log('[Company Employees DataTable] Tab switched event received:', data);

        // Initialize DataTable when staff tab becomes active
        if (data.tabId === 'staff') {
            console.log('[Company Employees DataTable] Staff tab activated');

            // Use retry logic to wait for AJAX content to load
            retryInitDataTable();
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
            retryInitDataTable();
        }
    });

})(jQuery);
