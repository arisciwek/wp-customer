<?php
/**
 * Employee DataTable Model
 *
 * Handles server-side DataTable processing for customer employees.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Employee
 * @version     2.1.1
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
 * 2.1.1 - 2025-12-30
 * - CRITICAL FIX: Added $this->columns property definition in constructor
 * - Fixes "Invalid JSON response" error in Employees tab DataTable
 * - Fixes SQL error: "SELECT  FROM" (missing column list)
 * - Now properly matches AbstractDataTable v2.0 requirements
 *
 * 2.1.0 - 2025-12-25
 * - Added: Role-based branch filtering for customer employees
 * - Regular customer_employee only see employees in their own branch
 * - Customer admins see all employees in their customer
 * - Support for explicit branch_id filtering (company staff tab)
 *
 * 2.0.0 - 2025-11-05
 * - ✅ REFACTORED: get_total_count() with QueryBuilder (48% reduction: 23 → 12 lines)
 * - ✅ REFACTORED: get_total_count_global() with QueryBuilder (36% reduction: 28 → 18 lines)
 * - ✅ FIXED: Single placeholder wpdb::prepare Notice (4 locations: line 199, 205, 275, 281-284)
 * - ✅ Replaced wpdb::prepare with sprintf + intval/esc_sql
 * - ✅ Improved whereRaw for subquery (permission-based filtering)
 * - ✅ Added QueryBuilder import
 * - ✅ Type-safe queries
 * - ✅ Backward compatible
 *
 * 1.2.0 - 2025-01-02 (FIX: Class Mismatch with JS Handler)
 * - Fixed: Changed button class from .employee-edit-btn to .edit-employee
 * - Fixed: Changed button class from .employee-delete-btn to .delete-employee
 * - Reason: Match with customer-datatable-v2.js handler classes
 * - Impact: Edit/Delete buttons now work correctly in employees tab
 * - Prevents: URL hash collision issue (#customer-X instead of staying on tab)
 *
 * 1.1.0 - 2025-11-02 (TODO-2190)
 * - Changed table alias from 'e' to 'ce' (customer_employee) untuk avoid conflicts
 * - Added $table_alias property for flexible alias management
 * - Added get_table_alias() method for JOIN operations
 * - All column references now use $table_alias variable
 * - Added customer_id to columns SELECT
 * - Added customer_id to DT_RowData
 * - Added data-customer-id attribute to Edit and Delete buttons
 *
 * 1.0.0 - 2025-11-01 (TODO-2187 Review-02)
 * - Initial implementation following wp-agency EmployeeDataTableModel pattern
 * - Columns: name, position, email, phone, status
 * - Filter by customer_id
 * - Includes get_total_count() for dashboard statistics
 */

namespace WPCustomer\Models\Employee;

use WPDataTable\Core\AbstractDataTable;
use WPQB\QueryBuilder;

defined('ABSPATH') || exit;

class EmployeeDataTableModel extends AbstractDataTable {

    /**
     * Table alias for JOINs
     * @var string
     */
    protected $table_alias = 'ce';

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_employees ' . $this->table_alias;
        $this->index_column = $this->table_alias . '.id';

        // Define searchable columns
        $this->searchable_columns = [
            $this->table_alias . '.name',
            $this->table_alias . '.position',
            $this->table_alias . '.email',
            $this->table_alias . '.phone'
        ];

        // JOIN to branches table for branch_name
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}app_customer_branches AS branches ON {$this->table_alias}.branch_id = branches.id"
        ];

        // CRITICAL: Define columns for SELECT clause (required by AbstractDataTable)
        $alias = $this->table_alias; // 'ce' for Employees tab
        $this->columns = [
            "{$alias}.name",
            "{$alias}.position",
            "{$alias}.email",
            "{$alias}.phone",
            "{$alias}.status",
            "{$alias}.id",
            "{$alias}.customer_id",
            "{$alias}.branch_id",
            "{$alias}.finance",
            "{$alias}.operation",
            "{$alias}.legal",
            "{$alias}.purchase",
            "branches.name as branch_name"
        ];

        // Base WHERE for customer filtering
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions (priority 5, before integrations)
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 5, 3);
    }

    /**
     * Get columns for SELECT clause
     *
     * DEPRECATED: Use $this->columns property instead.
     * Kept for backward compatibility only.
     *
     * @return array Column definitions
     */
    public function get_columns(): array {
        return $this->columns;
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    public function format_row($row): array {
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

        // Format department badges
        $department_badges = $this->generate_department_badges([
            'finance' => (bool)($row->finance ?? false),
            'operation' => (bool)($row->operation ?? false),
            'legal' => (bool)($row->legal ?? false),
            'purchase' => (bool)($row->purchase ?? false)
        ]);

        return [
            'DT_RowId' => 'employee-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'customer_id' => $row->customer_id ?? 0,
                'branch_id' => $row->branch_id ?? 0,
                'status' => $row->status ?? 'active',
                'entity' => 'employee'
            ],
            'name' => esc_html($row->name ?? ''),
            'position' => esc_html($row->position ?? '-'),
            'department' => $department_badges,
            'email' => esc_html($row->email ?? '-'),
            'branch_name' => esc_html($row->branch_name ?? '-'),
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
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // Edit button (shown for users with edit permission)
        if (current_user_can('manage_options') ||
            current_user_can('edit_all_customer_employees') ||
            current_user_can('edit_own_customer_employee')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small edit-employee" data-id="%d" data-customer-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr($row->customer_id ?? 0),
                esc_attr__('Edit Employee', 'wp-customer')
            );
        }

        // Delete button (shown for users with delete permission)
        if (current_user_can('manage_options') ||
            current_user_can('delete_all_customer_employees') ||
            current_user_can('delete_own_customer_employee')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small delete-employee" data-id="%d" data-customer-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr($row->customer_id ?? 0),
                esc_attr__('Delete Employee', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Generate department badges HTML
     *
     * @param array $departments Department flags ['finance' => true, 'operation' => false, etc.]
     * @return string HTML badges
     */
    protected function generate_department_badges(array $departments): string {
        $badges = [];

        $department_labels = [
            'finance' => __('Finance', 'wp-customer'),
            'operation' => __('Operation', 'wp-customer'),
            'legal' => __('Legal', 'wp-customer'),
            'purchase' => __('Purchase', 'wp-customer')
        ];

        foreach ($department_labels as $key => $label) {
            if (!empty($departments[$key])) {
                $badges[] = sprintf(
                    '<span class="department-badge department-%s">%s</span>',
                    esc_attr($key),
                    esc_html($label)
                );
            }
        }

        return !empty($badges) ? implode(' ', $badges) : '<span class="text-muted">-</span>';
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
        $alias = $this->table_alias;
        $current_user_id = get_current_user_id();

        error_log('[EmployeeDataTable] filter_where START');
        error_log('[EmployeeDataTable] Current user ID: ' . $current_user_id);
        error_log('[EmployeeDataTable] Request data: ' . print_r($request_data, true));
        error_log('[EmployeeDataTable] Initial WHERE conditions: ' . print_r($where_conditions, true));

        // Filter by customer_id (required)
        if (isset($request_data['customer_id'])) {
            $customer_id = (int) $request_data['customer_id'];
            // Use sprintf since customer_id is already cast to int
            $where_clause = sprintf("{$alias}.customer_id = %d", $customer_id);
            $where_conditions[] = $where_clause;
            error_log("[EmployeeDataTable] Added customer_id filter: {$where_clause}");
        } else {
            error_log('[EmployeeDataTable] WARNING: No customer_id in request_data!');
        }

        // Filter by branch_id (optional, for company staff tab OR from request)
        if (isset($request_data['branch_id']) && !empty($request_data['branch_id'])) {
            $branch_id = (int) $request_data['branch_id'];
            $branch_clause = sprintf("{$alias}.branch_id = %d", $branch_id);
            $where_conditions[] = $branch_clause;
            error_log("[EmployeeDataTable] Added branch_id filter from request: {$branch_clause}");
        } else {
            // Check if current user is a regular customer_employee (not admin)
            // Regular employees should only see employees in their own branch
            $user = wp_get_current_user();
            $is_customer_employee = in_array('customer_employee', $user->roles)
                                    && !in_array('customer_admin', $user->roles)
                                    && !in_array('customer_branch_admin', $user->roles)
                                    && !current_user_can('administrator');

            if ($is_customer_employee) {
                // Get user's branch_id from employee record
                $user_employee = $wpdb->get_row($wpdb->prepare(
                    "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d LIMIT 1",
                    $current_user_id
                ));

                if ($user_employee && $user_employee->branch_id) {
                    $user_branch_id = (int) $user_employee->branch_id;
                    $branch_clause = sprintf("{$alias}.branch_id = %d", $user_branch_id);
                    $where_conditions[] = $branch_clause;
                    error_log("[EmployeeDataTable] Regular employee - Added branch_id filter from user's branch: {$branch_clause}");
                } else {
                    error_log("[EmployeeDataTable] Regular employee but no branch_id found for user {$current_user_id}");
                }
            } else {
                error_log("[EmployeeDataTable] User is admin/manager - no branch_id restriction");
            }
        }

        // Filter by status (optional, from dropdown filter)
        if (isset($request_data['status_filter']) && !empty($request_data['status_filter'])) {
            $status = sanitize_text_field($request_data['status_filter']);
            // Use esc_sql since status is already sanitized
            $status_clause = sprintf("{$alias}.status = '%s'", esc_sql($status));
            $where_conditions[] = $status_clause;
            error_log("[EmployeeDataTable] Added status filter: {$status_clause}");
        } else {
            // Default to active if no filter specified
            $where_conditions[] = "{$alias}.status = 'active'";
            error_log("[EmployeeDataTable] Added default active filter");
        }

        error_log('[EmployeeDataTable] Final WHERE conditions: ' . print_r($where_conditions, true));
        return $where_conditions;
    }

    /**
     * Get table alias
     *
     * @return string Table alias for JOIN operations
     * @since 1.1.0
     */
    public function get_table_alias(): string {
        return $this->table_alias;
    }

    /**
     * Get total count with filtering (REFACTORED WITH QueryBuilder)
     *
     * Helper method for dashboard statistics.
     * Uses QueryBuilder for clean, type-safe queries.
     *
     * Before (Raw SQL - 23 lines):
     * - Manual array manipulation
     * - String concatenation
     * - Error-prone
     *
     * After (QueryBuilder - 12 lines):
     * - Clean method chaining
     * - Type-safe
     * - Maintainable
     *
     * @param int $customer_id Customer ID to filter by
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(int $customer_id, string $status_filter = 'active'): int {
        global $wpdb;
        $alias = $this->table_alias;

        // Build query with QueryBuilder
        $query = QueryBuilder::table($wpdb->prefix . "app_customer_employees as {$alias}")
            ->selectRaw("COUNT({$alias}.id) as total")
            ->where("{$alias}.customer_id", $customer_id);

        // Apply status filter
        if ($status_filter !== 'all' && !empty($status_filter)) {
            $query->where("{$alias}.status", $status_filter);
        }

        // Execute and return count
        $result = $query->first();
        return (int) ($result->total ?? 0);
    }

    /**
     * Get total count (global - all customers) (REFACTORED WITH QueryBuilder)
     *
     * For dashboard global statistics.
     * Includes permission-based filtering.
     *
     * Before (Raw SQL - 28 lines with 2x single placeholder prepare):
     * - Manual array manipulation
     * - String concatenation
     * - wpdb::prepare Notice issues
     *
     * After (QueryBuilder - 18 lines):
     * - Clean method chaining
     * - Type-safe
     * - No Notice errors
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count_global(string $status_filter = 'active'): int {
        global $wpdb;
        $alias = $this->table_alias;

        // Build base query
        $query = QueryBuilder::table($wpdb->prefix . "app_customer_employees as {$alias}")
            ->selectRaw("COUNT({$alias}.id) as total");

        // Filter by status
        if ($status_filter !== 'all' && !empty($status_filter)) {
            $query->where("{$alias}.status", $status_filter);
        }

        // Apply permission-based filtering (non-admin sees only their customers)
        if (!current_user_can('administrator')) {
            $user_id = get_current_user_id();
            // Use whereRaw with subquery for IN clause
            $query->whereRaw(
                "{$alias}.customer_id IN (SELECT customer_id FROM {$wpdb->prefix}app_customer_employees WHERE user_id = ?)",
                [$user_id]
            );
        }

        // Execute and return count
        $result = $query->first();
        return (int) ($result->total ?? 0);
    }
}
