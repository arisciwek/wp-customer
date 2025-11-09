/**
 * Company Invoice DataTable
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/CompanyInvoice
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/company-invoice/company-invoice-datatable.js
 *
 * Description: Minimal DataTable initialization for Company Invoice dashboard.
 *              Compatible with wp-datatable dual-panel system.
 *              DELEGATES all panel interactions to wp-datatable framework.
 *              Includes status filter handling.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-datatable panel-manager.js (handles all row/button clicks automatically)
 *
 * How it works:
 * 1. Initialize DataTable with server-side processing
 * 2. Server returns DT_RowData with invoice ID and status
 * 3. DataTables automatically converts DT_RowData to data-* attributes on <tr>
 * 4. wp-datatable panel-manager.js detects clicks on .wpdt-datatable rows
 * 5. Panel opens automatically - NO custom code needed!
 * 6. Status filters reload DataTable with filter parameters
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation following company-datatable.js pattern
 * - TRUE minimal implementation - delegates everything to framework
 * - Status filters: pending, paid, pending_payment, cancelled
 * - Columns: Invoice #, Company, From/To Level, Period, Amount, Status, Due Date, Actions
 */

(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[Company Invoice DataTable] Initializing...');

        var $table = $('#company-invoice-datatable');

        if ($table.length === 0) {
            console.log('[Company Invoice DataTable] Table element not found');
            return;
        }

        // Get nonce from wpdtConfig or wpCompanyInvoiceConfig
        var nonce = '';
        if (typeof wpdtConfig !== 'undefined' && wpdtConfig.nonce) {
            nonce = wpdtConfig.nonce;
            console.log('[Company Invoice DataTable] Using wpdtConfig.nonce');
        } else if (typeof wpCompanyInvoiceConfig !== 'undefined' && wpCompanyInvoiceConfig.nonce) {
            nonce = wpCompanyInvoiceConfig.nonce;
            console.log('[Company Invoice DataTable] Using wpCompanyInvoiceConfig.nonce');
        } else {
            console.error('[Company Invoice DataTable] No nonce available!');
        }

        // Initialize DataTable with server-side processing
        var invoiceTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_company_invoice_datatable';
                    d.nonce = nonce;

                    // Add status filters
                    d.filter_pending = $('#filter-pending').is(':checked') ? 1 : 0;
                    d.filter_paid = $('#filter-paid').is(':checked') ? 1 : 0;
                    d.filter_pending_payment = $('#filter-pending-payment').is(':checked') ? 1 : 0;
                    d.filter_cancelled = $('#filter-cancelled').is(':checked') ? 1 : 0;
                }
            },
            columns: [
                {
                    data: 'invoice_number',
                    name: 'invoice_number',
                    responsivePriority: 1  // Always visible
                },
                {
                    data: 'company_name',
                    name: 'company_name',
                    responsivePriority: 1  // Always visible
                },
                {
                    data: 'from_level_name',
                    name: 'from_level_name',
                    responsivePriority: 3  // Hide when panel opens
                },
                {
                    data: 'level_name',
                    name: 'level_name',
                    responsivePriority: 2  // Hide when panel opens
                },
                {
                    data: 'period_months',
                    name: 'period_months',
                    responsivePriority: 3  // Hide when panel opens
                },
                {
                    data: 'amount',
                    name: 'amount',
                    responsivePriority: 2  // Hide when panel opens
                },
                {
                    data: 'status',
                    name: 'status',
                    responsivePriority: 1,  // Always visible
                    render: function(data, type, row) {
                        // Server already sends HTML badge, just return it
                        return data;
                    }
                },
                {
                    data: 'due_date',
                    name: 'due_date',
                    responsivePriority: 2  // Hide when panel opens
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
                    $(row).attr('data-branch-id', data.DT_RowData.branch_id);
                    $(row).attr('data-status', data.DT_RowData.status);
                    $(row).attr('data-is-upgrade', data.DT_RowData.is_upgrade ? '1' : '0');
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
                emptyTable: 'No invoices found. Try changing the filter.',
                paginate: {
                    first: 'First',
                    previous: 'Previous',
                    next: 'Next',
                    last: 'Last'
                }
            }
        });

        console.log('[Company Invoice DataTable] DataTable initialized');

        // Register DataTable instance to panel manager
        if (window.wpdtPanelManager) {
            window.wpdtPanelManager.dataTable = invoiceTable;
            console.log('[Company Invoice DataTable] Registered to panel manager');
        } else {
            console.warn('[Company Invoice DataTable] Panel manager not found, will retry...');
            // Retry after a short delay
            setTimeout(function() {
                if (window.wpdtPanelManager) {
                    window.wpdtPanelManager.dataTable = invoiceTable;
                    console.log('[Company Invoice DataTable] Registered to panel manager (delayed)');
                }
            }, 500);
        }

        // Handle filter changes
        $('.wpdt-filters input[type="checkbox"]').on('change', function() {
            console.log('[Company Invoice DataTable] Filter changed, reloading table');
            invoiceTable.ajax.reload();
        });

        // Handle statistics update
        function updateStatistics() {
            console.log('[Company Invoice DataTable] Updating statistics...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_company_invoice_stats',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $('#stat-total-invoices').text(response.data.total || 0);
                        $('#stat-pending-invoices').text(response.data.pending || 0);
                        $('#stat-paid-invoices').text(response.data.paid || 0);
                        $('#stat-total-amount').text('Rp ' + formatNumber(response.data.total_amount || 0));
                        console.log('[Company Invoice DataTable] Statistics updated');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Company Invoice DataTable] Failed to load statistics:', error);
                }
            });
        }

        // Format number with thousand separator
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Update statistics on page load
        console.log('[Company Invoice DataTable] Calling updateStatistics on page load...');
        updateStatistics();

        // Update statistics when DataTable reloads
        $table.on('draw.dt', function() {
            console.log('[Company Invoice DataTable] DataTable reloaded, updating statistics...');
            updateStatistics();
        });

        // ==================================================================
        // ACTION HANDLERS (Invoice-specific actions)
        // ==================================================================

        // Handle edit invoice button
        $(document).on('click', '.invoice-edit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent row click

            var invoiceId = $(this).data('id');
            console.log('[Company Invoice DataTable] Edit invoice:', invoiceId);

            // TODO: Implement edit invoice modal/form
            alert('Edit invoice #' + invoiceId + ' - Feature coming soon');
        });

        // Handle cancel invoice button
        $(document).on('click', '.invoice-cancel-btn', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent row click

            var invoiceId = $(this).data('id');

            if (!confirm('Are you sure you want to cancel this invoice?')) {
                return;
            }

            console.log('[Company Invoice DataTable] Cancelling invoice:', invoiceId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cancel_invoice',
                    nonce: nonce,
                    invoice_id: invoiceId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Invoice cancelled successfully');
                        invoiceTable.ajax.reload();

                        // Close panel if open
                        if (window.wpdtPanelManager) {
                            window.wpdtPanelManager.closePanel();
                        }
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to cancel invoice'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Company Invoice DataTable] Cancel error:', error);
                    alert('Failed to cancel invoice');
                }
            });
        });

        // Handle validate payment button
        $(document).on('click', '.validate-payment-btn', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent row click

            var invoiceId = $(this).data('id');

            if (!confirm('Are you sure you want to validate this payment?')) {
                return;
            }

            console.log('[Company Invoice DataTable] Validating payment:', invoiceId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_invoice_payment',
                    nonce: nonce,
                    invoice_id: invoiceId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Payment validated successfully');
                        invoiceTable.ajax.reload();

                        // Reload panel to show updated status
                        if (window.wpdtPanelManager && window.wpdtPanelManager.currentEntity === 'company-invoice') {
                            window.wpdtPanelManager.loadDetailPanel(invoiceId, 'company-invoice');
                        }
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to validate payment'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Company Invoice DataTable] Validate payment error:', error);
                    alert('Failed to validate payment');
                }
            });
        });

        // Handle payment proof upload (TODO: implement file upload)
        $(document).on('submit', '#upload-payment-proof-form', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'upload_invoice_payment_proof');
            formData.append('nonce', nonce);

            console.log('[Company Invoice DataTable] Uploading payment proof...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Payment proof uploaded successfully');
                        invoiceTable.ajax.reload();

                        // Reload current tab to show uploaded proof
                        var currentTab = $('.wpdt-tab.active').attr('id');
                        if (currentTab && window.wpdtPanelManager) {
                            // Trigger tab reload
                            $(document).trigger('wpdt:reload-tab', [currentTab]);
                        }
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to upload payment proof'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Company Invoice DataTable] Upload error:', error);
                    alert('Failed to upload payment proof');
                }
            });
        });

        console.log('[Company Invoice DataTable] All initialized');
    });

})(jQuery);
