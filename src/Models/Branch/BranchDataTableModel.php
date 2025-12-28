<?php
/**
 * Branch DataTable Model
 *
 * Handles server-side DataTable processing for customer branches.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Branch
 * @version     2.2.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Branch/BranchDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              branches dari sebuah customer dengan lazy loading pattern.
 *              Filter otomatis berdasarkan customer_id.
 *              Columns: Kode, Nama Cabang, Tipe, Email, Telepon, Status
 *
 * Changelog:
 * 2.2.0 - 2025-12-26
 * - Added agency, division, and inspector columns
 * - Added JOINs to app_agencies, app_agency_divisions, app_agency_employees
 * - Display agency name, division name (unit kerja), and inspector name
 *
 * 2.1.0 - 2025-12-25
 * - Added: Role-based branch filtering
 * - customer_admin: see all branches in their customer
 * - customer_branch_admin: only see their managed branch
 * - customer_employee: only see their own branch
 * - Non-admin users automatically filtered by their branch_id
 *
 * 2.0.0 - 2025-11-05
 * - ✅ REFACTORED: get_total_count() with QueryBuilder (48% reduction: 23 → 12 lines)
 * - ✅ FIXED: Single placeholder wpdb::prepare Notice (line 199, 205)
 * - ✅ Replaced wpdb::prepare with sprintf + intval/esc_sql
 * - ✅ Added QueryBuilder import
 * - ✅ Type-safe queries
 * - ✅ Backward compatible
 *
 * 1.1.0 - 2025-11-02 (TODO-2190)
 * - Changed table alias from 'b' to 'cb' (customer_branch) untuk avoid conflicts
 * - Added $table_alias property for flexible alias management
 * - Added get_table_alias() method for JOIN operations
 * - All column references now use $table_alias variable
 * - Added customer_id to columns SELECT
 * - Added customer_id to DT_RowData
 * - Added data-customer-id attribute to Edit button
 *
 * 1.0.0 - 2025-11-01 (TODO-2187 Review-02)
 * - Initial implementation following wp-agency DivisionDataTableModel pattern
 * - Columns: code, name, type, email, phone, status
 * - Filter by customer_id
 * - Includes get_total_count() for dashboard statistics
 */

namespace WPCustomer\Models\Branch;

use WPDataTable\Core\AbstractDataTable;
use WPQB\QueryBuilder;

defined('ABSPATH') || exit;

class BranchDataTableModel extends AbstractDataTable {

    /**
     * Table alias for JOINs
     * @var string
     */
    protected $table_alias = 'cb';

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches ' . $this->table_alias;
        $this->index_column = $this->table_alias . '.id';

        // Define searchable columns
        $this->searchable_columns = [
            $this->table_alias . '.code',
            $this->table_alias . '.name',
            $this->table_alias . '.email',
            $this->table_alias . '.phone'
        ];

        // JOINs for agency, division, and inspector info
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}app_agencies a ON {$this->table_alias}.agency_id = a.id",
            "LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON {$this->table_alias}.division_id = d.id",
            "LEFT JOIN {$wpdb->prefix}app_agency_employees e ON {$this->table_alias}.inspector_id = e.id"
        ];

        // Base WHERE for customer filtering - MUST be empty, filtering via filter_where hook only
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions (customer_id and status filters)
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 10, 3);
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    public function get_columns(): array {
        $alias = $this->table_alias;
        return [
            "{$alias}.code as code",
            "{$alias}.name as name",
            "{$alias}.type as type",
            "{$alias}.email as email",
            "{$alias}.phone as phone",
            "a.name as agency_name",
            "d.name as division_name",
            "e.name as inspector_name",
            "{$alias}.status as status",
            "{$alias}.id as id",
            "{$alias}.customer_id as customer_id"
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    public function format_row($row): array {
        // Format type display
        $type_display = '';
        if (isset($row->type)) {
            $type_display = $row->type === 'pusat' ? 'Pusat' : 'Cabang';
        }

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
            'DT_RowId' => 'branch-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'customer_id' => $row->customer_id ?? 0,
                'status' => $row->status ?? 'active',
                'entity' => 'branch'
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'type' => esc_html($type_display),
            'email' => esc_html($row->email ?? '-'),
            'phone' => esc_html($row->phone ?? '-'),
            'agency' => esc_html($row->agency_name ?? '-'),
            'division' => esc_html($row->division_name ?? '-'),
            'inspector' => esc_html($row->inspector_name ?? '-'),
            'status' => $status_badge,
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate action buttons for branch row (TODO-2189)
     *
     * @param object $row Branch data
     * @return string HTML buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // Edit button (shown for users with edit permission)
        if (current_user_can('manage_options') ||
            current_user_can('edit_all_customer_branches') ||
            current_user_can('edit_own_customer_branch')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small branch-edit-btn" data-id="%d" data-customer-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr($row->customer_id ?? 0),
                esc_attr__('Edit Branch', 'wp-customer')
            );
        }

        // Delete button (shown for users with delete permission)
        if (current_user_can('manage_options') ||
            current_user_can('delete_all_customer_branches') ||
            current_user_can('delete_own_customer_branch')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small branch-delete-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Branch', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Filter WHERE conditions
     *
     * Hooked to: wpapp_datatable_customer_branches_where
     * Filters by customer_id and optionally status from request data
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        // IMPORTANT: Only apply this filter if $model is instance of BranchDataTableModel
        // This prevents conflicts with CompanyDataTableModel which uses same table
        if (!($model instanceof self)) {
            return $where_conditions;
        }

        global $wpdb;
        $alias = $this->table_alias;
        $current_user_id = get_current_user_id();

        // Filter by customer_id (required)
        if (isset($request_data['customer_id'])) {
            $customer_id = (int) $request_data['customer_id'];
            // Use sprintf since customer_id is already cast to int
            $where_conditions[] = sprintf("{$alias}.customer_id = %d", $customer_id);
        }

        // Role-based branch filtering
        // customer_admin: see all branches in their customer
        // customer_branch_admin: only see their managed branch
        // customer_employee: only see their own branch
        $user = wp_get_current_user();
        $is_customer_admin = in_array('customer_admin', $user->roles);
        $is_admin = current_user_can('administrator');

        // If not customer_admin and not system admin, filter by user's branch
        if (!$is_customer_admin && !$is_admin) {
            // Get user's branch_id from employee record
            $user_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d LIMIT 1",
                $current_user_id
            ));

            if ($user_employee && $user_employee->branch_id) {
                $user_branch_id = (int) $user_employee->branch_id;
                $branch_clause = sprintf("{$alias}.id = %d", $user_branch_id);
                $where_conditions[] = $branch_clause;
                error_log("[BranchDataTable] Non-admin user (ID: {$current_user_id}) - filtered to branch_id: {$user_branch_id}");
            } else {
                error_log("[BranchDataTable] Non-admin user (ID: {$current_user_id}) but no branch_id found");
            }
        } else {
            error_log("[BranchDataTable] Admin user (ID: {$current_user_id}) - no branch restriction");
        }

        // Filter by status (optional, from dropdown filter)
        if (isset($request_data['status_filter']) && !empty($request_data['status_filter'])) {
            $status = sanitize_text_field($request_data['status_filter']);
            // Use esc_sql since status is already sanitized
            $where_conditions[] = sprintf("{$alias}.status = '%s'", esc_sql($status));
        } else {
            // Default to active if no filter specified
            $where_conditions[] = "{$alias}.status = 'active'";
        }

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
     * - Manual array manipulation for WHERE
     * - String concatenation for SQL
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
        $query = QueryBuilder::table($wpdb->prefix . "app_customer_branches as {$alias}")
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
}
