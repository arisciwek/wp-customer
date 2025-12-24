/**
 * Customer DataTable
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     2.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-datatable.js
 *
 * Description: Minimal DataTable initialization for Customer dashboard.
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
 * 2. Server returns DT_RowData with customer ID
 * 3. DataTables automatically converts DT_RowData to data-* attributes on <tr>
 * 4. wp-datatable panel-manager.js detects clicks on .wpdt-datatable rows
 * 5. Panel opens automatically - NO custom code needed!
 *
 * Changelog:
 * 2.0.1 - 2025-11-09
 * - REMOVED: createdRow callback (unnecessary - DT_RowData handles this)
 * - REMOVED: Custom click handlers (wp-datatable handles automatically)
 * - REMOVED: registerDataTable call (panel-manager finds table by .wpdt-datatable class)
 * - Actions column now uses server-side HTML with wpdt-panel-trigger class
 * - TRUE minimal implementation - delegates everything to framework
 *
 * 2.0.0 - 2025-11-09
 * - BREAKING: Complete rewrite for wp-datatable compatibility
 * - Removed all custom panel handling
 * - Removed dependencies on CustomerToast, Customer, etc
 *
 * 1.0.2 - Previous version
 * - Complex custom implementation (deprecated)
 */

(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[Customer DataTable] Initializing...');

        var $table = $('#customer-datatable');

        if ($table.length === 0) {
            console.log('[Customer DataTable] Table element not found');
            return;
        }

        // Get nonce from wpdtConfig or wpCustomerConfig
        var nonce = '';
        if (typeof wpdtConfig !== 'undefined' && wpdtConfig.nonce) {
            nonce = wpdtConfig.nonce;
            console.log('[Customer DataTable] Using wpdtConfig.nonce');
        } else if (typeof wpCustomerConfig !== 'undefined' && wpCustomerConfig.nonce) {
            nonce = wpCustomerConfig.nonce;
            console.log('[Customer DataTable] Using wpCustomerConfig.nonce');
        } else {
            console.error('[Customer DataTable] No nonce available!');
        }

        // Initialize DataTable with server-side processing
        var customerTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_customer_datatable';
                    d.nonce = nonce;
                }
            },
            columns: [
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'npwp', name: 'npwp' },
                { data: 'nib', name: 'nib' },
                {
                    data: 'status',
                    name: 'status'
                    // No render function - Model sends HTML badge
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
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

        console.log('[Customer DataTable] DataTable initialized');

        // Register DataTable instance to panel manager
        // panel-manager.js might init before us, so we set it manually
        if (window.wpdtPanelManager) {
            window.wpdtPanelManager.dataTable = customerTable;
            console.log('[Customer DataTable] Registered to panel manager');
        } else {
            console.warn('[Customer DataTable] Panel manager not found');
        }

        // Export DataTable instance to global scope for external access
        window.customerDataTableInstance = customerTable;

        console.log('[Customer DataTable] Ready');

        // Listen to global action button events from wp-datatable
        // Edit button event
        $(document).on('wpdt:action-edit', function(e, data) {
            if (data.entity !== 'customer') {
                return; // Only handle customer entity
            }

            console.log('[Customer DataTable] Edit button clicked for customer:', data.id);

            // Check if WPModal and wpCustomerConfig are available
            if (typeof WPModal === 'undefined') {
                console.error('[Customer DataTable] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            if (typeof wpCustomerConfig === 'undefined') {
                console.error('[Customer DataTable] wpCustomerConfig not found!');
                alert('Customer config not available. Please refresh the page.');
                return;
            }

            // Show edit modal
            WPModal.show({
                type: 'form',
                title: 'Edit Customer',
                size: 'large',
                bodyUrl: wpCustomerConfig.ajaxUrl + '?action=get_customer_form&mode=edit&customer_id=' + data.id + '&nonce=' + wpCustomerConfig.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Update Customer',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: function(formData, $form) {
                    // Delegate to CustomerModalHandler if available
                    if (window.CustomerModalHandler && window.CustomerModalHandler.handleSave) {
                        return window.CustomerModalHandler.handleSave(formData, $form);
                    }
                    console.error('[Customer DataTable] CustomerModalHandler not found!');
                    return false;
                },
                onLoad: function() {
                    // Attach real-time validation if CustomerModalHandler available
                    if (window.CustomerModalHandler && window.CustomerModalHandler.attachRealtimeValidation) {
                        window.CustomerModalHandler.attachRealtimeValidation();
                    }
                }
            });
        });

        // Delete button event
        $(document).on('wpdt:action-delete', function(e, data) {
            if (data.entity !== 'customer') {
                return; // Only handle customer entity
            }

            console.log('[Customer DataTable] Delete button clicked for customer:', data.id);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[Customer DataTable] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show delete confirmation
            WPModal.confirm({
                title: 'Delete Customer',
                message: 'Are you sure you want to delete this customer? This action cannot be undone.',
                danger: true,
                confirmLabel: 'Delete',
                onConfirm: function() {
                    // Delegate to CustomerModalHandler if available
                    if (window.CustomerModalHandler && window.CustomerModalHandler.handleDelete) {
                        window.CustomerModalHandler.handleDelete(data.id);
                    } else {
                        console.error('[Customer DataTable] CustomerModalHandler not found!');
                    }
                }
            });
        });
    });

})(jQuery);
