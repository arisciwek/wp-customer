/**
 * Customer DataTable V2 - Centralized System
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Customer
 * @version     2.4.0
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
 * - wpAppModal (wp-app-core modal system)
 *
 * Changelog:
 * 2.4.0 - 2025-11-02 (TODO-2191 Employee CRUD Integration)
 * - Added: initEmployeeHandlers() - Employee CRUD handlers via centralized modal
 * - Added: Event handler for .employee-add-btn (create employee)
 * - Added: Event handler for .edit-employee (edit employee)
 * - Added: Event handler for .delete-employee (delete employee)
 * - Integrated: wp-app-core centralized modal system (wpAppModal)
 * - Form loading: via AJAX get_employee_form action
 * - Form submission: via create_customer_employee / update_customer_employee actions
 * - Delete: via delete_customer_employee action
 * - Pattern: Same as branch CRUD, consistent implementation
 *
 * 2.3.3 - 2025-11-02 (TODO-2190 Map Integration)
 * - Added: Map picker integration for branch coordinates
 * - Integrated: CustomerBranchMap adapter for modal lifecycle
 * - Added onOpen and onClose callbacks to initialize and cleanup map
 * - Map automatically initializes when modal opens
 * - Map automatically cleans up when modal closes
 *
 * 2.3.2 - 2025-11-02 (TODO-2190 Fix)
 * - Fixed: Changed nonce parameter from &nonce= to &_ajax_nonce=
 * - Fixes 403 Forbidden error when loading modal forms
 * - WordPress check_ajax_referer() expects '_ajax_nonce' in URL query
 *
 * 2.3.1 - 2025-11-02 (TODO-2190 Fix)
 * - Fixed: Edit branch handler now includes customer_id in URL parameter
 * - Fixed: Added e.stopPropagation() to prevent URL hash change
 * - Prevents event bubbling to row click listener
 * - URL hash stays at #customer-211&tab=branches (no change to #customer-70)
 * - Extract customer_id from data-customer-id attribute
 * - URL pattern: action=get_branch_form&id=70&customer_id=211
 *
 * 2.3.0 - 2025-11-02 (TODO-2190)
 * - Added: initBranchHandlers() - Branch CRUD handlers via modal
 * - Added: Event handler for .branch-add-btn (create branch)
 * - Added: Event handler for .branch-edit-btn (edit branch)
 * - Added: Event handler for .branch-delete-btn (delete branch)
 * - Integrated: wp-app-core centralized modal system (wpAppModal)
 * - Form loading: via AJAX get_branch_form action
 * - Form submission: via save_branch action
 * - Delete: via delete_branch action
 *
 * 2.2.1 - 2025-11-02 (TODO-2189 FINAL)
 * - Confirmed lazy-load works without flicker after wp-app-core animation fix
 * - Removed initPreRenderedTabs() - not needed with lazy-load
 * - Keep lazy-load pattern for performance (load on demand)
 * - Root cause was fadeIn animation (fixed in TODO-1197)
 *
 * 2.2.0 - 2025-11-02 (TODO-2189)
 * - Changed: Branches and Staff tabs from lazy-load to direct render
 * - Added: initPreRenderedTabs() to initialize DataTables on panel load
 * - Fixed: Eliminated flicker by rendering all tab content immediately
 * - Removed: AJAX loading delay for better UX
 *
 * 2.1.1 - 2025-11-02 (TODO-2189)
 * - Fixed: Tab switching flicker by caching initialized tabs
 * - Added: initializedTabs tracking to prevent re-initialization
 * - Reduced: setTimeout delay from 300ms to 100ms for faster response
 *
 * 2.1.0 - 2025-11-02 (TODO-2189)
 * - Added: initLazyDataTable() - Initialize lazy-loaded DataTables in tabs
 * - Added: initBranchesDataTable() - Initialize Branches DataTable
 * - Added: initEmployeesDataTable() - Initialize Employees DataTable
 * - Added: Event listener for 'wpapp:tab-switched' to auto-init lazy DataTables
 *
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
         * Track initialized lazy tabs to prevent flicker
         */
        initializedTabs: {},

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
            this.initBranchHandlers(); // TODO-2190
            this.initEmployeeHandlers(); // TODO-2191
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

            // TODO-2189: Listen to tab-switched event for lazy-loaded DataTables
            $(document).on('wpapp:tab-switched', (e, data) => {
                console.log('[CustomerDataTable] Tab switched:', data);

                // Skip if tab already initialized (prevent flicker)
                if (this.initializedTabs[data.tabId]) {
                    console.log('[CustomerDataTable] Tab', data.tabId, 'already initialized, skipping');
                    return;
                }

                this.initLazyDataTable(data.tabId);
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
         * Initialize lazy-loaded DataTable in tabs (TODO-2189)
         *
         * @param {string} tabId Tab ID that was switched to
         */
        initLazyDataTable(tabId) {
            console.log('[CustomerDataTable] initLazyDataTable called for tab:', tabId);

            // Wait a bit for tab content to be fully loaded (reduced from 300ms to 100ms)
            setTimeout(() => {
                const $tab = $('#' + tabId);
                console.log('[CustomerDataTable] Looking for lazy DataTable in tab:', tabId);

                // Find DataTable with customer-lazy-datatable class in this tab
                const $lazyTable = $tab.find('.customer-lazy-datatable');

                if ($lazyTable.length === 0) {
                    console.log('[CustomerDataTable] No lazy DataTable found in this tab');
                    return;
                }

                console.log('[CustomerDataTable] Found lazy DataTable:', $lazyTable.attr('id'));

                // Check if already initialized
                if ($.fn.DataTable.isDataTable($lazyTable)) {
                    console.log('[CustomerDataTable] DataTable already initialized');
                    this.initializedTabs[tabId] = true;
                    return;
                }

                // Get configuration from data attributes
                const ajaxAction = $lazyTable.data('ajax-action');
                const customerId = $lazyTable.data('customer-id');
                const tableId = $lazyTable.attr('id');

                console.log('[CustomerDataTable] Initializing lazy DataTable:', {
                    tableId: tableId,
                    ajaxAction: ajaxAction,
                    customerId: customerId
                });

                // Initialize DataTable based on table ID
                if (tableId === 'customer-branches-datatable') {
                    this.initBranchesDataTable($lazyTable, ajaxAction, customerId);
                    this.initializedTabs[tabId] = true;
                } else if (tableId === 'customer-employees-datatable') {
                    this.initEmployeesDataTable($lazyTable, ajaxAction, customerId);
                    this.initializedTabs[tabId] = true;
                }
            }, 100);
        },

        /**
         * Initialize Branches DataTable (TODO-2189)
         */
        initBranchesDataTable($table, ajaxAction, customerId) {
            console.log('[CustomerDataTable] Initializing Branches DataTable');

            $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAppCoreCustomer.ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        nonce: wpAppCoreCustomer.nonce,
                        customer_id: customerId
                    }
                },
                columns: [
                    { data: 'code', title: 'Kode', width: '10%' },
                    { data: 'name', title: 'Nama Cabang', width: '25%' },
                    { data: 'type', title: 'Tipe', width: '10%' },
                    { data: 'email', title: 'Email', width: '20%' },
                    { data: 'phone', title: 'Telepon', width: '15%' },
                    { data: 'status', title: 'Status', width: '10%' },
                    {
                        data: 'actions',
                        title: 'Actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '10%'
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    processing: 'Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    zeroRecords: 'Tidak ada data yang cocok',
                    emptyTable: 'Tidak ada data tersedia',
                    paginate: {
                        first: 'Pertama',
                        previous: 'Sebelumnya',
                        next: 'Selanjutnya',
                        last: 'Terakhir'
                    }
                }
            });

            console.log('[CustomerDataTable] Branches DataTable initialized');
        },

        /**
         * Initialize Employees DataTable (TODO-2189)
         */
        initEmployeesDataTable($table, ajaxAction, customerId) {
            console.log('[CustomerDataTable] Initializing Employees DataTable');

            $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAppCoreCustomer.ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        nonce: wpAppCoreCustomer.nonce,
                        customer_id: customerId
                    }
                },
                columns: [
                    { data: 'name', title: 'Nama', width: '25%' },
                    { data: 'position', title: 'Jabatan', width: '20%' },
                    { data: 'email', title: 'Email', width: '25%' },
                    { data: 'phone', title: 'Telepon', width: '15%' },
                    { data: 'status', title: 'Status', width: '10%' },
                    {
                        data: 'actions',
                        title: 'Actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '10%'
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    processing: 'Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    zeroRecords: 'Tidak ada data yang cocok',
                    emptyTable: 'Tidak ada data tersedia',
                    paginate: {
                        first: 'Pertama',
                        previous: 'Sebelumnya',
                        next: 'Selanjutnya',
                        last: 'Terakhir'
                    }
                }
            });

            console.log('[CustomerDataTable] Employees DataTable initialized');
        },

        /**
         * Initialize Branch CRUD Handlers (TODO-2190)
         */
        initBranchHandlers() {
            console.log('[CustomerDataTable] Initializing Branch CRUD handlers');

            const self = this;

            // Add Branch button
            $(document).on('click', '.branch-add-btn', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                console.log('[CustomerDataTable] Add Branch button clicked');

                const customerId = $(this).data('customer-id');

                wpAppModal.show({
                    type: 'form',
                    title: 'Tambah Cabang',
                    bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_branch_form&customer_id=' + customerId + '&_ajax_nonce=' + wpAppCoreCustomer.nonce,
                    size: 'large',
                    buttons: {
                        cancel: { label: 'Batal' },
                        submit: { label: 'Simpan Cabang', primary: true }
                    },
                    // Map initialization handled via jQuery events in customer-branch-map.js
                    onSubmit: function(formData, $form) {
                        console.log('[CustomerDataTable] Submitting create branch form');

                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Cabang berhasil ditambahkan',
                                        autoClose: 3000
                                    });

                                    // Reload Branches DataTable
                                    const $branchesTable = $('#customer-branches-datatable');
                                    if ($.fn.DataTable.isDataTable($branchesTable)) {
                                        $branchesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal menambahkan cabang'
                                    });
                                }
                            },
                            error: function(xhr) {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Edit Branch button
            $(document).on('click', '.branch-edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling to row click
                console.log('[CustomerDataTable] Edit Branch button clicked');

                const branchId = $(this).data('id');
                const customerId = $(this).data('customer-id');

                wpAppModal.show({
                    type: 'form',
                    title: 'Edit Cabang',
                    bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_branch_form&id=' + branchId + '&customer_id=' + customerId + '&_ajax_nonce=' + wpAppCoreCustomer.nonce,
                    size: 'large',
                    buttons: {
                        cancel: { label: 'Batal' },
                        submit: { label: 'Simpan Perubahan', primary: true }
                    },
                    // Map initialization handled via jQuery events in customer-branch-map.js
                    onSubmit: function(formData, $form) {
                        console.log('[CustomerDataTable] Submitting edit branch form');

                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Cabang berhasil diperbarui',
                                        autoClose: 3000
                                    });

                                    // Reload Branches DataTable
                                    const $branchesTable = $('#customer-branches-datatable');
                                    if ($.fn.DataTable.isDataTable($branchesTable)) {
                                        $branchesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal memperbarui cabang'
                                    });
                                }
                            },
                            error: function(xhr) {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Delete Branch button
            $(document).on('click', '.branch-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling to row click
                console.log('[CustomerDataTable] Delete Branch button clicked');

                const branchId = $(this).data('id');

                wpAppModal.confirm({
                    title: 'Hapus Cabang?',
                    message: 'Apakah Anda yakin ingin menghapus cabang ini? Tindakan ini tidak dapat dibatalkan.',
                    danger: true,
                    confirmLabel: 'Hapus',
                    onConfirm: function() {
                        console.log('[CustomerDataTable] Deleting branch:', branchId);

                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'delete_branch',
                                id: branchId,
                                nonce: wpAppCoreCustomer.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Cabang berhasil dihapus',
                                        autoClose: 3000
                                    });

                                    // Reload Branches DataTable
                                    const $branchesTable = $('#customer-branches-datatable');
                                    if ($.fn.DataTable.isDataTable($branchesTable)) {
                                        $branchesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal menghapus cabang'
                                    });
                                }
                            },
                            error: function(xhr) {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });
        },

        /**
         * Initialize Employee CRUD Handlers (TODO-2191)
         */
        initEmployeeHandlers() {
            console.log('[CustomerDataTable] Initializing Employee CRUD handlers');

            const self = this;

            // Add Employee button
            $(document).on('click', '.employee-add-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[CustomerDataTable] Add Employee button clicked');

                const customerId = $(this).data('customer-id');

                wpAppModal.show({
                    type: 'form',
                    title: 'Tambah Karyawan',
                    bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_employee_form&customer_id=' + customerId + '&_ajax_nonce=' + wpAppCoreCustomer.nonce,
                    size: 'large',
                    buttons: {
                        cancel: { label: 'Batal' },
                        submit: { label: 'Simpan Karyawan', primary: true }
                    },
                    onSubmit: function(formData, $form) {
                        console.log('[CustomerDataTable] Submitting create employee form');

                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Karyawan berhasil ditambahkan',
                                        autoClose: 3000
                                    });

                                    // Reload Employees DataTable
                                    const $employeesTable = $('#customer-employees-datatable');
                                    if ($employeesTable.length && $.fn.DataTable.isDataTable($employeesTable)) {
                                        $employeesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal menambah karyawan'
                                    });
                                }
                            },
                            error: function() {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Edit Employee button
            $(document).on('click', '.edit-employee', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[CustomerDataTable] Edit Employee button clicked');

                const employeeId = $(this).data('id');
                const customerId = $(this).data('customer-id');

                wpAppModal.show({
                    type: 'form',
                    title: 'Edit Karyawan',
                    bodyUrl: wpAppCoreCustomer.ajaxurl + '?action=get_employee_form&id=' + employeeId + '&customer_id=' + customerId + '&_ajax_nonce=' + wpAppCoreCustomer.nonce,
                    size: 'large',
                    buttons: {
                        cancel: { label: 'Batal' },
                        submit: { label: 'Update Karyawan', primary: true }
                    },
                    onSubmit: function(formData, $form) {
                        console.log('[CustomerDataTable] Submitting update employee form');

                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            method: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Karyawan berhasil diperbarui',
                                        autoClose: 3000
                                    });

                                    // Reload Employees DataTable
                                    const $employeesTable = $('#customer-employees-datatable');
                                    if ($employeesTable.length && $.fn.DataTable.isDataTable($employeesTable)) {
                                        $employeesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal memperbarui karyawan'
                                    });
                                }
                            },
                            error: function() {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
            });

            // Delete Employee button
            $(document).on('click', '.delete-employee', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[CustomerDataTable] Delete Employee button clicked');

                const employeeId = $(this).data('id');

                wpAppModal.confirm({
                    title: 'Hapus Karyawan?',
                    message: 'Apakah Anda yakin ingin menghapus karyawan ini? Tindakan ini tidak dapat dibatalkan.',
                    danger: true,
                    confirmLabel: 'Hapus',
                    onConfirm: function() {
                        $.ajax({
                            url: wpAppCoreCustomer.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'delete_customer_employee',
                                id: employeeId,
                                nonce: wpAppCoreCustomer.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    wpAppModal.info({
                                        infoType: 'success',
                                        title: 'Berhasil',
                                        message: response.data.message || 'Karyawan berhasil dihapus',
                                        autoClose: 3000
                                    });

                                    // Reload Employees DataTable
                                    const $employeesTable = $('#customer-employees-datatable');
                                    if ($employeesTable.length && $.fn.DataTable.isDataTable($employeesTable)) {
                                        $employeesTable.DataTable().ajax.reload();
                                    }
                                } else {
                                    wpAppModal.info({
                                        infoType: 'error',
                                        title: 'Error',
                                        message: response.data.message || 'Gagal menghapus karyawan'
                                    });
                                }
                            },
                            error: function() {
                                wpAppModal.info({
                                    infoType: 'error',
                                    title: 'Error',
                                    message: 'Terjadi kesalahan pada server'
                                });
                            }
                        });
                    }
                });
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
