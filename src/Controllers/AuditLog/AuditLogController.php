<?php
/**
 * Audit Log Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/AuditLog
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/AuditLog/AuditLogController.php
 *
 * Description: Controller untuk audit log DataTable AJAX requests.
 *              Handles get_audit_logs AJAX action.
 *              Returns JSON response untuk DataTable.
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial implementation
 * - AJAX handler for audit log DataTable
 * - Supports customer + related entities filtering
 */

namespace WPCustomer\Controllers\AuditLog;

use WPCustomer\Models\AuditLog\AuditLogDataTableModel;

defined('ABSPATH') || exit;

class AuditLogController {

    private $model;

    public function __construct() {
        $this->model = new AuditLogDataTableModel();
        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers(): void {
        add_action('wp_ajax_get_audit_logs', [$this, 'handleGetAuditLogs']);
    }

    /**
     * Handle get audit logs AJAX request
     */
    public function handleGetAuditLogs(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_customer_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        // Check permissions (user must be logged in at minimum)
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'wp-customer')]);
        }

        try {
            // Get DataTable parameters
            $request_data = [
                'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
                'start' => isset($_POST['start']) ? intval($_POST['start']) : 0,
                'length' => isset($_POST['length']) ? intval($_POST['length']) : 10,
                'search' => isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '',
                'order' => isset($_POST['order']) ? $_POST['order'] : [],
                'columns' => isset($_POST['columns']) ? $_POST['columns'] : [],

                // Custom parameters
                'customer_id' => isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0,
            ];

            // Validate customer_id
            if (empty($request_data['customer_id'])) {
                wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
            }

            // Permission check: User can only view audit logs for entities they have access to
            // This is handled by RoleBasedFilter in the DataTableModel
            // Admin sees all, customer_admin sees their customer only

            // Get data from model
            $response = $this->model->get_datatable_data($request_data);

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle view audit detail (for modal)
     */
    public function handleViewAuditDetail(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_customer_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'wp-customer')]);
        }

        try {
            $audit_id = isset($_POST['audit_id']) ? intval($_POST['audit_id']) : 0;

            if (empty($audit_id)) {
                wp_send_json_error(['message' => __('Invalid audit ID', 'wp-customer')]);
            }

            global $wpdb;
            $audit_log = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}app_customer_audit_logs WHERE id = %d",
                $audit_id
            ));

            if (!$audit_log) {
                wp_send_json_error(['message' => __('Audit log not found', 'wp-customer')]);
            }

            // Decode JSON values
            $old_values = !empty($audit_log->old_values) ? json_decode($audit_log->old_values, true) : [];
            $new_values = !empty($audit_log->new_values) ? json_decode($audit_log->new_values, true) : [];

            // Permission check: User can only view audit logs they have access to
            // TODO: Add permission validation based on auditable_type and auditable_id

            wp_send_json_success([
                'audit_log' => [
                    'id' => $audit_log->id,
                    'auditable_type' => $audit_log->auditable_type,
                    'auditable_id' => $audit_log->auditable_id,
                    'event' => $audit_log->event,
                    'old_values' => $old_values,
                    'new_values' => $new_values,
                    'user_id' => $audit_log->user_id,
                    'ip_address' => $audit_log->ip_address,
                    'created_at' => $audit_log->created_at
                ]
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }
}
