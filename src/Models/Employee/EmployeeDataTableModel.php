<?php
/**
 * Employee DataTable Model
 *
 * Handles server-side DataTable processing for customer employees.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Employee/EmployeeDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              employees dari sebuah customer dengan lazy loading pattern.
 *              Filter otomatis berdasarkan customer_id.
 *              Columns: Nama, Jabatan, Email, Telepon, Status
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187 Review-02)
 * - Initial implementation following wp-agency EmployeeDataTableModel pattern
 * - Columns: name, position, email, phone, status
 * - Filter by customer_id
 * - Includes get_total_count() for dashboard statistics
 */

namespace WPCustomer\Models\Employee;

use WPAppCore\Models\DataTable\DataTableModel;

defined('ABSPATH') || exit;

class EmployeeDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_employees e';
        $this->index_column = 'e.id';

        // Define searchable columns
        $this->searchable_columns = [
            'e.name',
            'e.position',
            'e.email',
            'e.phone'
        ];

        // No joins needed for employees
        $this->base_joins = [];

        // Base WHERE for customer filtering
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions (priority 5, before integrations)
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 5, 3);
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        return [
            'e.name as name',
            'e.position as position',
            'e.email as email',
            'e.phone as phone',
            'e.status as status',
            'e.id as id'
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        // Format status badge
        $status_badge = '';
        if (isset($row->status)) {
            $badge_class = $row->status === 'active' ? 'status-active' : 'status-inactive';
            $status_text = $row->status === 'active' ? __('Active', 'wp-customer') : __('Inactive', 'wp-customer');
            $status_badge = sprintf(
                '<span class="status-badge %s">%s</span>',
                $badge_class,
                $status_text
            );
        }

        return [
            'DT_RowId' => 'employee-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'status' => $row->status ?? 'active',
                'entity' => 'employee'
            ],
            'name' => esc_html($row->name ?? ''),
            'position' => esc_html($row->position ?? '-'),
            'email' => esc_html($row->email ?? '-'),
            'phone' => esc_html($row->phone ?? '-'),
            'status' => $status_badge,
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate action buttons for employee row (TODO-2189)
     *
     * @param object $row Employee data
     * @return string HTML buttons
     */
    private function generate_action_buttons($row): string {
        $buttons = [];

        // Edit button (shown for users with edit permission)
        if (current_user_can('manage_options') ||
            current_user_can('edit_all_customer_employees') ||
            current_user_can('edit_own_customer_employee')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small employee-edit-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Employee', 'wp-customer')
            );
        }

        // Delete button (shown for users with delete permission)
        if (current_user_can('manage_options') ||
            current_user_can('delete_all_customer_employees') ||
            current_user_can('delete_own_customer_employee')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small employee-delete-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Employee', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Filter WHERE conditions
     *
     * Hooked to: wpapp_datatable_customer_employees_where (priority 5)
     * Filters by customer_id and optionally status from request data
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        global $wpdb;

        // Filter by customer_id (required)
        if (isset($request_data['customer_id'])) {
            $customer_id = (int) $request_data['customer_id'];
            $where_conditions[] = $wpdb->prepare('e.customer_id = %d', $customer_id);
        }

        // Filter by status (optional, from dropdown filter)
        if (isset($request_data['status_filter']) && !empty($request_data['status_filter'])) {
            $status = sanitize_text_field($request_data['status_filter']);
            $where_conditions[] = $wpdb->prepare('e.status = %s', $status);
        } else {
            // Default to active if no filter specified
            $where_conditions[] = "e.status = 'active'";
        }

        return $where_conditions;
    }

    /**
     * Get total count with filtering
     *
     * Helper method for dashboard statistics.
     * Reuses same filtering logic as DataTable.
     *
     * @param int $customer_id Customer ID to filter by
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(int $customer_id, string $status_filter = 'active'): int {
        global $wpdb;

        // Prepare request data for filtering
        $request_data = [
            'customer_id' => $customer_id,
            'status_filter' => $status_filter
        ];

        // Build WHERE conditions using same logic as DataTable
        $where_conditions = $this->filter_where([], $request_data, $this);

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(e.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }

    /**
     * Get total count (global - all customers)
     *
     * For dashboard global statistics.
     * Includes permission-based filtering.
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count_global(string $status_filter = 'active'): int {
        global $wpdb;

        $where_conditions = [];

        // Filter by status
        if ($status_filter !== 'all') {
            $where_conditions[] = $wpdb->prepare('e.status = %s', $status_filter);
        }

        // Apply permission-based filtering (non-admin sees only their customers)
        if (!current_user_can('administrator')) {
            $user_id = get_current_user_id();
            $where_conditions[] = $wpdb->prepare(
                "e.customer_id IN (SELECT customer_id FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d)",
                $user_id
            );
        }

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(e.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
