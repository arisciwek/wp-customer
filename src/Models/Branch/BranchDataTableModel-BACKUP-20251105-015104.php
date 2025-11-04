<?php
/**
 * Branch DataTable Model
 *
 * Handles server-side DataTable processing for customer branches.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Branch
 * @version     1.1.0
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

use WPAppCore\Models\DataTable\DataTableModel;

defined('ABSPATH') || exit;

class BranchDataTableModel extends DataTableModel {

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

        // No joins needed for branches
        $this->base_joins = [];

        // Base WHERE for customer filtering
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions
        add_filter($this->get_filter_hook('where'), [$this, 'filter_where'], 10, 3);
    }

    /**
     * Get columns for SELECT clause
     *
     * @return array Column definitions
     */
    protected function get_columns(): array {
        $alias = $this->table_alias;
        return [
            "{$alias}.code as code",
            "{$alias}.name as name",
            "{$alias}.type as type",
            "{$alias}.email as email",
            "{$alias}.phone as phone",
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
    protected function format_row($row): array {
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
    private function generate_action_buttons($row): string {
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
        global $wpdb;
        $alias = $this->table_alias;

        // Filter by customer_id (required)
        if (isset($request_data['customer_id'])) {
            $customer_id = (int) $request_data['customer_id'];
            $where_conditions[] = $wpdb->prepare("{$alias}.customer_id = %d", $customer_id);
        }

        // Filter by status (optional, from dropdown filter)
        if (isset($request_data['status_filter']) && !empty($request_data['status_filter'])) {
            $status = sanitize_text_field($request_data['status_filter']);
            $where_conditions[] = $wpdb->prepare("{$alias}.status = %s", $status);
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

        $count_sql = "SELECT COUNT(b.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
