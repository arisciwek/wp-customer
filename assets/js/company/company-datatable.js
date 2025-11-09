/**
 * Company DataTable
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
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
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation following customer-datatable.js pattern
 * - TRUE minimal implementation - delegates everything to framework
 * - Columns: Code, Name, Type, Email, Phone, Status, Actions
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
                {
                    data: 'code',
                    name: 'code',
                    responsivePriority: 1  // Always visible
                },
                {
                    data: 'name',
                    name: 'name',
                    responsivePriority: 1  // Always visible
                },
                {
                    data: 'type',
                    name: 'type',
                    responsivePriority: 2  // Hide when panel opens
                },
                {
                    data: 'email',
                    name: 'email',
                    responsivePriority: 3  // Hide when panel opens (lower priority)
                },
                {
                    data: 'phone',
                    name: 'phone',
                    responsivePriority: 2  // Hide when panel opens
                },
                {
                    data: 'status',
                    name: 'status',
                    responsivePriority: 1  // Always visible (no render, Model sends HTML badge)
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 1  // Always visible (actions always needed)
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
