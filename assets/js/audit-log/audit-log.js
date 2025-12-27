/**
 * Audit Log / History Tab Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/AuditLog
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/audit-log/audit-log.js
 *
 * Description: JavaScript untuk audit log history tab.
 *              Handles DataTable initialization and detail modal.
 *              Auto-initializes when history tab becomes visible.
 *
 * Dependencies:
 * - jQuery
 * - DataTables (loaded by wp-datatable BaseAssets)
 * - wp-datatable tab-manager.js (handles tab switching)
 *
 * Changelog:
 * 1.0.1 - 2025-12-28
 * - Fixed initialization timing using wpdt:tab-switched event
 * - Following branches-datatable.js pattern
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - DataTable initialization with server-side processing
 * - Detail modal with old/new value comparison
 */

(function($) {
    'use strict';

    let auditTable = null;

    /**
     * Initialize Audit Log DataTable
     */
    function initAuditLogDataTable() {
        const $table = $('#audit-log-datatable');

        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        const customerId = $table.data('customer-id');

        if (!customerId) {
            console.error('[Audit Log] customer-id not found');
            return;
        }

        // Initialize DataTable
        auditTable = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_audit_logs';
                d.nonce = wpCustomerAuditLog.nonce;
                d.customer_id = customerId;
            },
            error: function(xhr, error, code) {
                console.error('DataTable AJAX Error:', error, code);
            }
        },
        columns: [
            { data: 'created_at', orderable: true },
            { data: 'entity', orderable: false },
            { data: 'event', orderable: false },
            { data: 'changes', orderable: false },
            { data: 'user', orderable: false },
            { data: 'actions', orderable: false }
        ],
        order: [[0, 'desc']], // Sort by date desc (newest first)
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: wpCustomerAuditLog.i18n
    });

        console.log('[Audit Log] DataTable initialized for customer:', customerId);

        // View audit detail button click
        $table.on('click', '.view-audit-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const auditId = $(this).data('id');
        const rowData = auditTable.row($(this).closest('tr')).data();

        if (!rowData || !rowData.DT_RowData) {
            console.error('No row data found');
            return;
        }

        showAuditDetailModal(rowData.DT_RowData);
        });
    }

    /**
     * Show audit detail modal with old/new values comparison
     */
    function showAuditDetailModal(data) {
        const oldValues = data.old_values || {};
        const newValues = data.new_values || {};

        let comparisonHTML = '<table class="audit-comparison-table">';
        comparisonHTML += '<thead><tr><th>' + wpCustomerAuditLog.i18n.field + '</th><th>' + wpCustomerAuditLog.i18n.oldValue + '</th><th>' + wpCustomerAuditLog.i18n.newValue + '</th></tr></thead>';
        comparisonHTML += '<tbody>';

        // Combine all fields from both old and new
        const allFields = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);

        allFields.forEach(field => {
            // Skip excluded fields
            if (['updated_at', 'created_at', 'updated_by', 'created_by'].includes(field)) {
                return;
            }

            const oldVal = oldValues[field] !== undefined ? oldValues[field] : '-';
            const newVal = newValues[field] !== undefined ? newValues[field] : '-';

            // Parse reference format "ID|Label" - show only label
            const oldDisplay = parseReferenceValue(oldVal);
            const newDisplay = parseReferenceValue(newVal);

            // Highlight if changed
            const changed = oldVal !== newVal ? 'audit-field-changed' : '';

            comparisonHTML += `<tr class="${changed}">`;
            comparisonHTML += `<td><strong>${escapeHtml(field)}</strong></td>`;
            comparisonHTML += `<td>${escapeHtml(String(oldDisplay))}</td>`;
            comparisonHTML += `<td>${escapeHtml(String(newDisplay))}</td>`;
            comparisonHTML += '</tr>';
        });

        comparisonHTML += '</tbody></table>';

        // Use WPModal if available, otherwise use native alert
        if (typeof WPModal !== 'undefined') {
            WPModal.show({
                type: 'info',
                title: wpCustomerAuditLog.i18n.detailTitle,
                body: comparisonHTML,
                size: 'large',
                buttons: {
                    close: {
                        label: wpCustomerAuditLog.i18n.close,
                        primary: false
                    }
                }
            });
        } else {
            alert(wpCustomerAuditLog.i18n.modalLibraryNotLoaded);
        }
    }

    /**
     * Parse reference value format "ID|Label"
     * Returns label if format matches, otherwise returns original value
     *
     * @param {*} value Value to parse
     * @return {string} Parsed value
     */
    function parseReferenceValue(value) {
        // Return as-is if not string
        if (typeof value !== 'string') {
            return value;
        }

        // Check if format is "ID|Label"
        if (value.includes('|')) {
            const parts = value.split('|');
            // Return label (second part)
            return parts[1] || value;
        }

        return value;
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Listen for tab switching event
     */
    $(document).on('wpdt:tab-switched', function(e, data) {
        // Initialize DataTable when history tab becomes active
        if (data.tabId === 'history') {
            console.log('[Audit Log] Tab switched to history');

            // Small delay to ensure DOM is ready
            setTimeout(function() {
                initAuditLogDataTable();
            }, 100);
        }
    });

    /**
     * Document ready - check if history tab is already active
     */
    $(document).ready(function() {
        // Check if history tab is active on page load (e.g., from hash)
        const $historyTab = $('.nav-tab[data-tab="history"]');

        if ($historyTab.hasClass('nav-tab-active')) {
            console.log('[Audit Log] History tab active on load');
            setTimeout(function() {
                initAuditLogDataTable();
            }, 100);
        }
    });

})(jQuery);
