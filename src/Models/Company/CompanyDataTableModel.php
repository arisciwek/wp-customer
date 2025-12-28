<?php
/**
 * Company DataTable Model
 *
 * Handles server-side DataTable processing for companies.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              companies (branches) dengan row click support untuk
 *              wp-datatable DualPanel framework.
 *              Includes View button dengan wpdt-panel-trigger class.
 *
 * Changelog:
 * 1.1.0 - 2025-12-26
 * - Added agency, division, and inspector columns
 * - Added JOINs to app_agencies, app_agency_divisions, app_agency_employees
 * - Display agency name (Disnaker), division name (Unit Kerja), and inspector name (Pengawas)
 * - Columns: Code, Name, Type, Email, Phone, Disnaker, Unit Kerja, Pengawas, Actions
 *
 * 1.0.1 - 2025-12-25
 * - Removed status column from display
 * - Always filter to show only active companies
 * - Edit access controlled by edit_all_customer_branches permission
 * - Columns: Code, Name, Type, Email, Phone, Actions
 *
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation for Company dashboard
 * - Based on BranchDataTableModel but with View button
 * - View button has wpdt-panel-trigger class for dual panel
 * - Filters companies only (no customer_id filter)
 */

namespace WPCustomer\Models\Company;

use WPDataTable\Core\AbstractDataTable;
use WPQB\QueryBuilder;

defined('ABSPATH') || exit;

class CompanyDataTableModel extends AbstractDataTable {

    /**
     * Table alias for JOINs
     * @var string
     */
    protected $table_alias = 'cc';

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

        // Base WHERE for filtering
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions
        // Note: Use 'company' entity type for access filter
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
            "{$alias}.id as id",
            "{$alias}.customer_id as customer_id",
            "{$alias}.status as status"
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

        return [
            'DT_RowId' => 'company-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'customer_id' => $row->customer_id ?? 0,
                'status' => $row->status ?? 'active',
                'entity' => 'company'
            ],
            'code' => esc_html($row->code ?? ''),
            'name' => esc_html($row->name ?? ''),
            'type' => esc_html($type_display),
            'email' => esc_html($row->email ?? '-'),
            'phone' => esc_html($row->phone ?? '-'),
            'agency' => esc_html($row->agency_name ?? '-'),
            'division' => esc_html($row->division_name ?? '-'),
            'inspector' => esc_html($row->inspector_name ?? '-'),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate action buttons for company row
     *
     * @param object $row Company data
     * @return string HTML buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // View button (ALWAYS shown for detail panel trigger)
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="company" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-customer')
        );

        // Edit button (shown for users with edit permission)
        if (current_user_can('manage_options') ||
            current_user_can('edit_all_customer_branches') ||
            current_user_can('edit_own_customer_branch')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small company-edit-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Company', 'wp-customer')
            );
        }

        // Delete button (shown for users with delete permission)
        if (current_user_can('manage_options') ||
            current_user_can('delete_all_customer_branches') ||
            current_user_can('delete_own_customer_branch')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small company-delete-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete Company', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Filter WHERE conditions
     *
     * Hooked to: wpapp_datatable_company_where
     * Filters by accessible branch IDs and active status only
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        // IMPORTANT: Only apply this filter if $model is instance of CompanyDataTableModel
        // This prevents conflicts with BranchDataTableModel which uses same table
        if (!($model instanceof self)) {
            return $where_conditions;
        }

        global $wpdb;
        $alias = $this->table_alias;

        error_log('[CompanyDataTable] filter_where START');
        error_log('[CompanyDataTable] Current user ID: ' . get_current_user_id());

        // Role-based filtering will be handled by RoleBasedFilter hook
        // No need for manual filtering here anymore

        // Always filter to show only active companies
        $where_conditions[] = "{$alias}.status = 'active'";
        error_log('[CompanyDataTable] Added active filter');

        error_log('[CompanyDataTable] Final WHERE conditions: ' . print_r($where_conditions, true));

        return $where_conditions;
    }

    /**
     * Override removed - use parent class role-based hook system
     * Parent class now generates: wpapp_datatable_where_customer_admin (role-based)
     * Instead of: wpapp_datatable_company_where (entity-based)
     *
     * @since 2.0.0 - Migrated to role-based hook system
     */

    /**
     * Get table alias
     *
     * @return string Table alias for JOIN operations
     */
    public function get_table_alias(): string {
        return $this->table_alias;
    }

    /**
     * Get total count with filtering
     *
     * Helper method for dashboard statistics.
     * Uses QueryBuilder for clean, type-safe queries.
     *
     * @param string $status_filter Status to filter (active/inactive/all)
     * @return int Total count
     */
    public function get_total_count(string $status_filter = 'active'): int {
        global $wpdb;
        $alias = $this->table_alias;

        // Build query with QueryBuilder
        $query = QueryBuilder::table($wpdb->prefix . "app_customer_branches as {$alias}")
            ->selectRaw("COUNT({$alias}.id) as total");

        // Apply status filter
        if ($status_filter !== 'all' && !empty($status_filter)) {
            $query->where("{$alias}.status", $status_filter);
        }

        // Execute and return count
        $result = $query->first();
        return (int) ($result->total ?? 0);
    }
}
