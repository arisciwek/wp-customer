/**
 * Customer DataTable V2 - Centralized System
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/customer/customer-datatable-v2.js
 *
 * Description: DataTable handler for Customer V2 menu using centralized system.
 *              Follows platform-staff-datatable.js pattern from wp-app-core.
 *              Integrates with base panel system.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-app-core base panel system
 * - wpAppCoreCustomer localized object
 *
 * Changelog:
 * 2.0.0 - 2025-11-01
 * - Initial version following platform-staff-datatable.js pattern
 * - Table ID: #customer-list-table
 * - AJAX action: get_customer_datatable
 */

(function($) {
    'use strict';

    /**
     * Customer DataTable Module
     */
    const CustomerDataTable = {

        /**
         * DataTable instance
         */
        table: null,

        /**
         * Initialization flag
         */
        initialized: false,

        /**
         * Initialize DataTable
         */
        init() {
            if (this.initialized) {
                console.log('[CustomerDataTable] Already initialized');
                return;
            }

            // Check if table element exists
            const tableId = '#customer-list-table';
            if ($(tableId).length === 0) {
                console.log('[CustomerDataTable] Table element not found: ' + tableId);
                return;
            }

            console.log('[CustomerDataTable] Table found: ' + tableId);

            // Check dependencies
            if (typeof wpAppCoreCustomer === 'undefined') {
                console.error('[CustomerDataTable] wpAppCoreCustomer object not found.');
                return;
            }

            console.log('[CustomerDataTable] Initializing...');

            this.initDataTable();
            this.bindEvents();
            this.loadStatistics();

            this.initialized = true;
            console.log('[CustomerDataTable] Initialized successfully');
        },

        /**
         * Initialize DataTable with server-side processing
         */
        initDataTable() {
            const statusFilter = $('#customer-status-filter').val() || 'aktif';

            this.table = $('#customer-list-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAppCoreCustomer.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_customer_datatable';
                        d.nonce = wpAppCoreCustomer.nonce;
                        d.status_filter = statusFilter;
                    }
                },
                columns: [
                    { data: 'code', title: wpAppCoreCustomer.i18n.code || 'Code' },
                    { data: 'name', title: wpAppCoreCustomer.i18n.name || 'Name' },
                    { data: 'npwp', title: wpAppCoreCustomer.i18n.npwp || 'NPWP' },
                    { data: 'nib', title: wpAppCoreCustomer.i18n.nib || 'NIB' },
                    { data: 'email', title: wpAppCoreCustomer.i18n.email || 'Email' },
                    {
                        data: 'actions',
                        title: wpAppCoreCustomer.i18n.actions || 'Actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                columnDefs: [
                    { width: '10%', targets: 0 },  // Code
                    { width: '22%', targets: 1 },  // Name
                    { width: '18%', targets: 2 },  // NPWP (reduced for Actions)
                    { width: '16%', targets: 3 },  // NIB (reduced for Actions)
                    { width: '20%', targets: 4 },  // Email (reduced for Actions)
                    { width: '14%', targets: 5 }   // Actions (increased for 3 buttons)
                ],
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: wpAppCoreCustomer.i18n.processing || 'Processing...',
                    search: wpAppCoreCustomer.i18n.search || 'Search:',
                    lengthMenu: wpAppCoreCustomer.i18n.lengthMenu || 'Show _MENU_ entries',
                    info: wpAppCoreCustomer.i18n.info || 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: wpAppCoreCustomer.i18n.infoEmpty || 'Showing 0 to 0 of 0 entries',
                    infoFiltered: wpAppCoreCustomer.i18n.infoFiltered || '(filtered from _MAX_ total entries)',
                    zeroRecords: wpAppCoreCustomer.i18n.zeroRecords || 'No matching records found',
                    emptyTable: wpAppCoreCustomer.i18n.emptyTable || 'No data available in table',
                    paginate: {
                        first: wpAppCoreCustomer.i18n.first || 'First',
                        previous: wpAppCoreCustomer.i18n.previous || 'Previous',
                        next: wpAppCoreCustomer.i18n.next || 'Next',
                        last: wpAppCoreCustomer.i18n.last || 'Last'
                    }
                },
                dom: '<"datatable-header"f>t<"datatable-footer"lip>',
                drawCallback: function() {
                    console.log('[CustomerDataTable] Table redrawn');
                }
            });

            console.log('[CustomerDataTable] DataTable initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Status filter change
            $(document).on('change', '#customer-status-filter', () => {
                console.log('[CustomerDataTable] Status filter changed');
                if (this.table) {
                    this.table.ajax.reload();
                }
            });

            // Row click for panel integration
            // Note: Buttons handled by wpapp-panel-manager.js via wpapp-panel-trigger class
            $(document).on('click', '#customer-list-table tbody tr', function(e) {
                // Don't trigger if clicking on a button
                if ($(e.target).closest('button').length) {
                    return;
                }

                const rowData = CustomerDataTable.table.row(this).data();
                if (rowData && rowData.DT_RowData) {
                    console.log('[CustomerDataTable] Row clicked, opening panel for ID:', rowData.DT_RowData.id);
                    $(document).trigger('wpapp:open-panel', [{
                        id: rowData.DT_RowData.id,
                        entity: rowData.DT_RowData.entity
                    }]);
                }
            });

            // Listen to panel open/close events for responsive columns
            $(document).on('wpapp:panel-opened', () => {
                console.log('[CustomerDataTable] Panel opened - hiding NPWP & NIB columns');
                if (this.table) {
                    // Hide columns: NPWP (index 2) and NIB (index 3)
                    this.table.column(2).visible(false);
                    this.table.column(3).visible(false);
                    this.table.columns.adjust().draw(false);
                }
            });

            $(document).on('wpapp:panel-closed', () => {
                console.log('[CustomerDataTable] Panel closed - showing all columns');
                if (this.table) {
                    // Show all columns
                    this.table.column(2).visible(true);
                    this.table.column(3).visible(true);
                    this.table.columns.adjust().draw(false);
                }
            });

            console.log('[CustomerDataTable] Events bound');
        },

        /**
         * Load statistics via AJAX
         */
        loadStatistics() {
            console.log('[CustomerDataTable] Loading statistics...');
            console.log('[CustomerDataTable] AJAX URL:', wpAppCoreCustomer.ajaxurl);
            console.log('[CustomerDataTable] Nonce:', wpAppCoreCustomer.nonce);

            $.ajax({
                url: wpAppCoreCustomer.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_customer_stats_v2',  // V2: Different action to avoid conflict with old menu
                    nonce: wpAppCoreCustomer.nonce
                },
                success: function(response) {
                    console.log('[CustomerDataTable] Statistics AJAX response:', response);

                    if (response.success && response.data) {
                        console.log('[CustomerDataTable] Statistics loaded successfully:', response.data);
                        console.log('[CustomerDataTable] - Total:', response.data.total);
                        console.log('[CustomerDataTable] - Active:', response.data.active);
                        console.log('[CustomerDataTable] - Inactive:', response.data.inactive);

                        // Update DOM elements
                        const $total = $('#stat-total-customers');
                        const $active = $('#stat-active-customers');
                        const $inactive = $('#stat-inactive-customers');

                        console.log('[CustomerDataTable] Found elements:', {
                            total: $total.length,
                            active: $active.length,
                            inactive: $inactive.length
                        });

                        if ($total.length > 0) {
                            $total.text(response.data.total);
                            console.log('[CustomerDataTable] Updated #stat-total-customers to:', response.data.total);
                        } else {
                            console.error('[CustomerDataTable] Element #stat-total-customers not found!');
                        }

                        if ($active.length > 0) {
                            $active.text(response.data.active);
                            console.log('[CustomerDataTable] Updated #stat-active-customers to:', response.data.active);
                        } else {
                            console.error('[CustomerDataTable] Element #stat-active-customers not found!');
                        }

                        if ($inactive.length > 0) {
                            $inactive.text(response.data.inactive);
                            console.log('[CustomerDataTable] Updated #stat-inactive-customers to:', response.data.inactive);
                        } else {
                            console.error('[CustomerDataTable] Element #stat-inactive-customers not found!');
                        }
                    } else {
                        console.warn('[CustomerDataTable] Statistics load failed:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[CustomerDataTable] Statistics AJAX error:', error);
                    console.error('[CustomerDataTable] XHR status:', xhr.status);
                    console.error('[CustomerDataTable] Response text:', xhr.responseText);
                }
            });
        },

        /**
         * Refresh DataTable
         */
        refresh() {
            if (this.table) {
                console.log('[CustomerDataTable] Refreshing table...');
                this.table.ajax.reload(null, false);
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[CustomerDataTable] Document ready');
        CustomerDataTable.init();
    });

    // Export to global scope
    window.CustomerDataTable = CustomerDataTable;

})(jQuery);
