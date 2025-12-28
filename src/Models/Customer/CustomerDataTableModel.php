<?php
/**
 * Customer DataTable Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Customer
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Customer/CustomerDataTableModel.php
 *
 * Description: DataTable model untuk server-side processing customer.
 *              NOW extends AbstractDataTable from wp-datatable (v2.0).
 *              Uses WordPress native $wpdb pattern.
 *              Maintains complex JOINs dan role-based filtering.
 *
 * Key Changes from v2.0.0:
 * - ✅ BREAKING: Changed to extend WPDataTable\Core\AbstractDataTable
 * - ✅ Removed dependency on deprecated wp-app-core DataTableModel
 * - ✅ Uses WordPress native $wpdb pattern (QueryBuilder optional)
 * - ✅ Implements get_entity_name() and get_text_domain()
 * - ✅ Updated badge classes: wpapp-badge → wpdt-badge
 * - ✅ Updated get_where() → get_where_conditions()
 * - ✅ Maintained role-based filtering hook
 * - ✅ Full backward compatibility for data output
 *
 * Changelog:
 * 3.0.0 - 2025-12-28
 * - Migrated to AbstractDataTable from wp-datatable
 * - Removed DataTableModel dependency
 * - Updated to WordPress native $wpdb pattern
 * - Updated badge classes to wpdt-badge
 * - Maintained all functionality and hooks
 *
 * 2.0.0 - 2025-11-05
 * - Refactored to use WP-QB QueryBuilder
 * - Improved get_total_count() with fluent interface
 *
 * 1.0.2 - 2025-11-01
 * - Added role-based filtering in get_where() method
 */

namespace WPCustomer\Models\Customer;

use WPDataTable\Core\AbstractDataTable;
use WPQB\QueryBuilder;

class CustomerDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;

        $this->table = $wpdb->prefix . 'app_customers c';
        $this->index_column = 'c.id';

        // Columns to SELECT in SQL query
        $this->columns = [
            'c.id as id',
            'c.code as code',
            'c.name as name',
            'c.npwp as npwp',
            'c.nib as nib',
            'c.status as status',
            'b.email as email'
        ];

        // Define searchable columns for global search
        $this->searchable_columns = [
            'c.code',
            'c.name',
            'c.npwp',
            'c.nib',
            'b.email'
        ];

        // Complex JOIN with subquery to get email from first branch
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
     * Get entity name for this DataTable
     *
     * @return string
     */
    public function get_entity_name(): string {
        return 'customer';
    }

    /**
     * Get text domain for translations
     *
     * @return string
     */
    public function get_text_domain(): string {
        return 'wp-customer';
    }

    /**
     * Format row data for DataTable output
     *
     * ✅ KEPT - No DB queries here, pure formatting
     *
     * @param object $row Database row object
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

        return [
            'DT_RowId' => 'customer-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => 'customer'
            ],
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            'npwp' => esc_html($row->npwp ?? '-'),
            'nib' => esc_html($row->nib ?? '-'),
            'status' => $status_badge,
            'email' => esc_html($row->email ?? '-'),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Override to add status filter support.
     * Role-based filtering is handled by CustomerRoleFilter via hook.
     *
     * @param array $request_data DataTables request data
     * @return array WHERE conditions
     */
    public function get_where_conditions(array $request_data): array {
        global $wpdb;
        $where = [];

        // Status filter
        $status_filter = isset($_POST['status_filter'])
            ? sanitize_text_field($_POST['status_filter'])
            : 'active';

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('c.status = %s', $status_filter);
        }

        // Role-based filtering is handled by CustomerRoleFilter integration class
        // via wpapp_datatable_customers_where hook

        return $where;
    }

    /**
     * Get total count with permission filtering (REFACTORED WITH QueryBuilder)
     *
     * ✅ REFACTORED - Cleaner code, maintained hook system
     *
     * Before (Raw SQL - 50 lines with POST manipulation):
     * - Manual POST hack
     * - String concatenation for WHERE
     * - Messy hook integration
     *
     * After (QueryBuilder - 28 lines):
     * - No POST manipulation needed
     * - Clean hook integration
     * - Type-safe queries
     *
     * IMPORTANT: Still applies wpapp_datatable_customers_where filter!
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(string $status_filter = 'active'): int {
        global $wpdb;

        // Build base query with QueryBuilder
        $query = QueryBuilder::table($wpdb->prefix . 'app_customers as c')
            ->selectRaw('COUNT(DISTINCT c.id) as total');

        // Apply status filter
        if ($status_filter !== 'all') {
            $query->where('c.status', $status_filter);
        }

        /**
         * CRITICAL: Apply role-based filter hook (CustomerRoleFilter)
         *
         * This hook allows CustomerRoleFilter to modify the query
         * for non-admin users based on their customer associations.
         *
         * The filter receives the QueryBuilder instance and can add
         * additional WHERE conditions via method chaining.
         *
         * Example in CustomerRoleFilter:
         * add_filter('wpapp_datatable_customers_count_query', function($query, $params) {
         *     if (!current_user_can('manage_options')) {
         *         $query->whereIn('c.id', $accessible_customer_ids);
         *     }
         *     return $query;
         * }, 10, 2);
         */
        $query = apply_filters('wpapp_datatable_customers_count_query', $query, [
            'status_filter' => $status_filter
        ]);

        // Execute and return count
        $result = $query->first();
        return (int) ($result->total ?? 0);
    }

    /**
     * Get complex data with JOINs (EXAMPLE: If you want to override parent's get_data)
     *
     * ✅ OPTIONAL - Only implement if you override parent's get_data()
     *
     * This shows how to handle complex JOINs with QueryBuilder.
     * The subquery for branch email becomes much cleaner!
     *
     * @param array $params DataTable parameters
     * @return array Query results
     */
    public function getComplexDataExample(array $params): array {
        global $wpdb;

        $status_filter = $params['status_filter'] ?? 'aktif';
        $search_value = $params['search']['value'] ?? '';
        $order_column = $params['order_column'] ?? 'code';
        $order_dir = $params['order_dir'] ?? 'ASC';
        $length = $params['length'] ?? 10;
        $start = $params['start'] ?? 0;

        // Build query with complex JOINs
        $query = QueryBuilder::table($wpdb->prefix . 'app_customers as c')
            ->select('c.*', 'b.email as email');

        // Complex JOIN: Get email from first branch with email
        // Using raw SQL for the subquery (QueryBuilder handles it!)
        $subquery = "(
            SELECT customer_id, MIN(id) as branch_id
            FROM {$wpdb->prefix}app_customer_branches
            WHERE email IS NOT NULL
            GROUP BY customer_id
        ) bmin";

        $query->leftJoin($subquery, 'c.id', '=', 'bmin.customer_id')
              ->leftJoin($wpdb->prefix . 'app_customer_branches as b', 'bmin.branch_id', '=', 'b.id');

        // Status filter
        if ($status_filter !== 'all') {
            $query->where('c.status', $status_filter);
        }

        // Search filter (nested WHERE - BEAUTIFUL!)
        if (!empty($search_value)) {
            $query->where(function($q) use ($search_value) {
                $q->where('c.code', 'LIKE', "%{$search_value}%")
                  ->orWhere('c.name', 'LIKE', "%{$search_value}%")
                  ->orWhere('c.npwp', 'LIKE', "%{$search_value}%")
                  ->orWhere('c.nib', 'LIKE', "%{$search_value}%")
                  ->orWhere('b.email', 'LIKE', "%{$search_value}%");
            });
        }

        // HOOK: Role-based filtering
        $query = apply_filters('wpapp_datatable_customers_query', $query, $params);

        // Order and pagination
        $query->orderBy("c.{$order_column}", $order_dir)
              ->limit($length)
              ->offset($start);

        return $query->get()->toArray();
    }

    /**
     * Format status badge with color coding
     *
     * ✅ UPDATED - Changed to wpdt-badge classes
     *
     * @param string $status Status value
     * @return string HTML badge
     */
    protected function format_status_badge(string $status, array $options = []): string {
        $badge_class = $status === 'active' ? 'success' : 'error';
        $status_text = $status === 'active'
            ? __('Active', 'wp-customer')
            : __('Inactive', 'wp-customer');

        return sprintf(
            '<span class="wpdt-badge wpdt-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate action buttons for each row
     *
     * ✅ KEPT - Pure HTML generation
     *
     * @param object $row Database row object
     * @return string HTML action buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // View button - uses wpdt-panel-trigger for wp-datatable integration
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="customer" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-customer')
        );

        // Edit button
        if (current_user_can('manage_options') || current_user_can('edit_all_customers') || current_user_can('edit_own_customer')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small customer-edit-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Customer', 'wp-customer')
            );
        }

        // Delete button
        if (current_user_can('manage_options') || current_user_can('delete_customers')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small customer-delete-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Customer', 'wp-customer')
            );
        }

        return '<div class="wpdt-action-buttons">' . implode(' ', $buttons) . '</div>';
    }

}
