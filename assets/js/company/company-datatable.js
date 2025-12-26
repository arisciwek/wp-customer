/**
 * Company DataTable
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company/company-datatable.js
 *
 * Description: Minimal DataTable initialization for Company dashboard.
 *              Compatible with wp-datatable dual-panel system.
 *              DELEGATES all panel interactions to wp-datatable framework.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-datatable panel-manager.js (handles all row/button clicks automatically)
 *
 * How it works:
 * 1. Initialize DataTable with server-side processing
 * 2. Server returns DT_RowData with company ID
 * 3. DataTables automatically converts DT_RowData to data-* attributes on <tr>
 * 4. wp-datatable panel-manager.js detects clicks on .wpdt-datatable rows
 * 5. Panel opens automatically - NO custom code needed!
 *
 * Changelog:
 * 1.1.0 - 2025-12-26
 * - Added agency, division, and inspector columns
 * - Columns: Code, Name, Type, Email, Phone, Agency, Division, Inspector, Actions
 * - Updated columnDefs targets (Actions moved from 5 to 8)
 *
 * 1.0.1 - 2025-12-25
 * - Removed status column (only active companies shown)
 * - Columns: Code, Name, Type, Email, Phone, Actions
 *
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation following customer-datatable.js pattern
 * - TRUE minimal implementation - delegates everything to framework
 */

(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[Company DataTable] Initializing...');

        var $table = $('#company-datatable');

        if ($table.length === 0) {
            console.log('[Company DataTable] Table element not found');
            return;
        }

        // Get nonce from wpdtConfig or wpCompanyConfig
        var nonce = '';
        if (typeof wpdtConfig !== 'undefined' && wpdtConfig.nonce) {
            nonce = wpdtConfig.nonce;
            console.log('[Company DataTable] Using wpdtConfig.nonce');
        } else if (typeof wpCompanyConfig !== 'undefined' && wpCompanyConfig.nonce) {
            nonce = wpCompanyConfig.nonce;
            console.log('[Company DataTable] Using wpCompanyConfig.nonce');
        } else {
            console.error('[Company DataTable] No nonce available!');
        }

        // Destroy existing table if it exists (for hot reload)
        if ($.fn.DataTable.isDataTable('#company-datatable')) {
            $('#company-datatable').DataTable().destroy();
        }

        // Initialize DataTable with server-side processing
        var companyTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_company_datatable';
                    d.nonce = nonce;
                }
            },
            columns: [
                { data: 'code' },
                { data: 'name' },
                { data: 'type' },
                { data: 'email' },
                { data: 'phone' },
                { data: 'agency' },
                { data: 'division' },
                { data: 'inspector' },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            columnDefs: [
                {
                    targets: 0,  // Code
                    responsivePriority: 1
                },
                {
                    targets: 1,  // Name
                    responsivePriority: 1
                },
                {
                    targets: 2,  // Type
                    responsivePriority: 2
                },
                {
                    targets: 3,  // Email
                    responsivePriority: 3
                },
                {
                    targets: 4,  // Phone
                    responsivePriority: 2
                },
                {
                    targets: 5,  // Agency (Disnaker)
                    responsivePriority: 3
                },
                {
                    targets: 6,  // Division (Unit Kerja)
                    responsivePriority: 3
                },
                {
                    targets: 7,  // Inspector (Pengawas)
                    responsivePriority: 3
                },
                {
                    targets: 8,  // Actions
                    responsivePriority: 1,
                    orderable: false,
                    searchable: false
                }
            ],
            createdRow: function(row, data, dataIndex) {
                // Copy DT_RowData to row attributes for panel-manager.js
                if (data.DT_RowData) {
                    $(row).attr('data-id', data.DT_RowData.id);
                    $(row).attr('data-entity', data.DT_RowData.entity);
                    $(row).attr('data-customer-id', data.DT_RowData.customer_id);
                    $(row).attr('data-status', data.DT_RowData.status);
                }
            },
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                processing: 'Processing...',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                zeroRecords: 'No matching records found',
                emptyTable: 'No data available in table',
                paginate: {
                    first: 'First',
                    previous: 'Previous',
                    next: 'Next',
                    last: 'Last'
                }
            }
        });

        console.log('[Company DataTable] DataTable initialized');

        // Register DataTable instance to panel manager
        // panel-manager.js might init before us, so we set it manually
        if (window.wpdtPanelManager) {
            window.wpdtPanelManager.dataTable = companyTable;
            console.log('[Company DataTable] Registered to panel manager');
        } else {
            console.warn('[Company DataTable] Panel manager not found, will retry...');
            // Retry after a short delay
            setTimeout(function() {
                if (window.wpdtPanelManager) {
                    window.wpdtPanelManager.dataTable = companyTable;
                    console.log('[Company DataTable] Registered to panel manager (delayed)');
                }
            }, 500);
        }

        console.log('[Company DataTable] Ready');
    });

})(jQuery);
