<?php
/**
 * Customer DataTable Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Customer
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Customer/CustomerDataTableModel.php
 *
 * Description: DataTable model untuk server-side processing customer.
 *              Extends base DataTableModel dari wp-app-core.
 *              Implements columns, joins, dan row formatting.
 *              Integrates dengan base panel system.
 *              Joins with branches table to get corporate email from head office.
 *              Implements role-based filtering for non-admin users.
 *
 * Changelog:
 * 1.0.2 - 2025-11-01 (Review-01 from TODO-2187)
 * - Added role-based filtering in get_where() method
 * - Non-admin users can only see customers they're associated with
 * - Filter by customer_id from wp_app_customer_employees table
 * - Administrator sees all customers without restriction
 * - Uses WP_Customer_Role_Manager to check plugin roles
 *
 * 1.0.1 - 2025-11-01
 * - Fixed email column to use corporate email from branches table
 * - Changed JOIN from wp_users to wp_app_customer_branches
 * - Uses subquery to get first branch with email (MIN(id) WHERE email IS NOT NULL)
 * - Updated searchable_columns to use b.email instead of u.user_email
 *
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following PlatformStaffDataTableModel pattern
 * - Extends WPAppCore\Models\DataTable\DataTableModel
 * - Define columns: code, name, npwp, nib, email, status, actions
 * - Implement get_columns() method
 * - Implement format_row() with DT_RowId and DT_RowData for panel
 * - Add format_status_badge() helper
 * - Add generate_action_buttons() helper
 * - Add get_total_count() for dashboard statistics
 * - Add get_where() for status filtering
 */

namespace WPCustomer\Models\Customer;

use WPAppCore\Models\DataTable\DataTableModel;

class CustomerDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;

        $this->table = $wpdb->prefix . 'app_customers c';  // Include alias 'c' for columns
        $this->index_column = 'c.id';

        // Define searchable columns for global search
        $this->searchable_columns = [
            'c.code',
            'c.name',
            'c.npwp',
            'c.nib',
            'b.email'
        ];

        // Define base JOINs to get email from first branch with email
        // Using subquery to get MIN(id) branch that has email per customer
        $this->base_joins = [
            "LEFT JOIN (
                SELECT customer_id, MIN(id) as branch_id
                FROM {$wpdb->prefix}app_customer_branches
                WHERE email IS NOT NULL
                GROUP BY customer_id
            ) bmin ON c.id = bmin.customer_id",
            "LEFT JOIN {$wpdb->prefix}app_customer_branches b ON bmin.branch_id = b.id"
        ];
    }

    /**
     * Get columns configuration for DataTable
     *
     * Defines all columns with their properties
     *
     * @return array Columns configuration
     */
    protected function get_columns(): array {
        return [
            'c.id as id',
            'c.code as code',
            'c.name as name',
            'c.npwp as npwp',
            'c.nib as nib',
            'b.email as email'
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * Applies proper escaping and formatting to each row.
     * Adds DT_RowId and DT_RowData for panel functionality.
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        return [
            'DT_RowId' => 'customer-' . $row->id,  // Required for panel open
            'DT_RowData' => [
                'id' => $row->id,                  // Required for panel AJAX
                'entity' => 'customer'             // Required for panel entity detection
            ],
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            'npwp' => esc_html($row->npwp ?? '-'),
            'nib' => esc_html($row->nib ?? '-'),
            'email' => esc_html($row->email ?? '-'),
            'actions' => $this->generate_action_buttons($row)  // View button for panel trigger
        ];
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Applies status filter only.
     * Role-based filtering is handled by CustomerRoleFilter via hook wpapp_datatable_customers_where.
     *
     * Note: This method is called by get_total_count() for statistics.
     * The base DataTableModel uses $this->base_where and filter hooks, not this method.
     *
     * @return array WHERE conditions
     */
    public function get_where(): array {
        global $wpdb;
        $where = [];

        // Status filter
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'aktif';

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('c.status = %s', $status_filter);
        }

        // Role-based filtering is handled by CustomerRoleFilter integration class
        // via wpapp_datatable_customers_where hook (Review-01 from TODO-2187)
        // See: /wp-customer/src/Integrations/CustomerRoleFilter.php

        return $where;
    }

    /**
     * Format status badge with color coding
     *
     * @param string $status Status value
     * @return string HTML badge
     */
    private function format_status_badge(string $status): string {
        $badge_class = $status === 'aktif' ? 'success' : 'error';
        $status_text = $status === 'aktif'
            ? __('Active', 'wp-customer')
            : __('Inactive', 'wp-customer');

        return sprintf(
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate action buttons for each row
     *
     * V2: Only view button for panel trigger.
     * Edit/delete buttons removed for simplicity.
     *
     * @param object $row Database row object
     * @return string HTML action buttons
     */
    private function generate_action_buttons($row): string {
        $buttons = [];

        // View button (always shown, opens panel)
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpapp-panel-trigger" data-id="%d" data-entity="customer" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-customer')
        );

        // Edit button (shown for admin or users with edit permission)
        if (current_user_can('manage_options') || current_user_can('edit_all_customers') || current_user_can('edit_own_customer')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small customer-edit-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Customer', 'wp-customer')
            );
        }

        // Delete button (shown for admin only)
        if (current_user_can('manage_options') || current_user_can('delete_customers')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small customer-delete-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Customer', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Get table alias for WHERE/JOIN clauses
     *
     * @return string Table alias
     */
    protected function get_table_alias(): string {
        return 'c';
    }

    /**
     * Get total count with permission filtering
     *
     * Helper method for dashboard statistics.
     * Applies role-based filtering via wpapp_datatable_customers_where hook.
     *
     * IMPORTANT: Applies wpapp_datatable_customers_where filter for role-based filtering!
     *
     * @param string $status_filter Status to filter (aktif/nonaktif/all)
     * @return int Total count
     */
    public function get_total_count(string $status_filter = 'aktif'): int {
        global $wpdb;

        // Prepare minimal request data for counting
        $request_data = [
            'start' => 0,
            'length' => 1,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'status_filter' => $status_filter
        ];

        // Temporarily set POST for get_where() method
        $original_post = $_POST;
        $_POST['status_filter'] = $status_filter;

        // Build WHERE conditions (status filter only)
        $where_conditions = $this->get_where();

        /**
         * CRITICAL: Apply role-based filter hook (CustomerRoleFilter)
         *
         * This filter allows CustomerRoleFilter to add additional WHERE conditions
         * for non-admin users based on their customer associations.
         *
         * Without this, statistics would show ALL customers instead of
         * only customers the user has access to.
         */
        $where_conditions = apply_filters(
            'wpapp_datatable_customers_where',
            $where_conditions,
            $request_data,
            $this
        );

        // Restore original POST
        $_POST = $original_post;

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        // Use DISTINCT COUNT
        $count_sql = "SELECT COUNT(DISTINCT c.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
