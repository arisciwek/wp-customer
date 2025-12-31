<?php
/**
 * Company DataTable Model
 *
 * Handles server-side DataTable processing for companies.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.1.1
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
 * 1.1.1 - 2025-12-30
 * - CRITICAL FIX: Changed table alias from 'cb' to 'cc'
 * - 'cc' = customer_company (Companies page)
 * - 'cb' = customer_branch (Branches tab in Customer detail)
 * - Now correctly matches RoleBasedFilter expectations in both wp-customer and wp-agency
 * - Fixes conflict with NewCompanyDataTableModel filter
 *
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
     * MUST be 'cc' (customer_company) for Companies page
     * Different from BranchDataTableModel which uses 'cb' (customer_branch)
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

        // CRITICAL: Define columns for SELECT clause (required by AbstractDataTable)
        $alias = $this->table_alias; // 'cc' for Companies page
        $this->columns = [
            "{$alias}.code",
            "{$alias}.name",
            "{$alias}.type",
            "{$alias}.email",
            "{$alias}.phone",
            "a.name as agency_name",
            "d.name as division_name",
            "e.name as inspector_name",
            "{$alias}.id",
            "{$alias}.customer_id",
            "{$alias}.status"
        ];

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

        // Base WHERE: Always filter to show only active companies
        $this->base_where = [
            "{$alias}.status = 'active'"
        ];
    }

    /**
     * Get columns for SELECT clause
     *
     * NOTE: This method is now deprecated - columns are defined in $this->columns
     * Kept for backward compatibility only
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
        // Format type display
        $type_display = '';
        if (isset($row->type)) {
            $type_display = $row->type === 'pusat' ? 'Pusat' : 'Cabang';
        }

        return [
            'DT_RowId' => 'company-' . ($row->id ?? 0),
            'DT_RowClass' => 'wpdt-clickable-row',
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
     * Filter WHERE conditions (REMOVED - moved to base_where)
     *
     * Active filter is now in $this->base_where in constructor.
     * Role-based filtering handled by parent class hook system.
     *
     * @deprecated Kept for backward compatibility
     */

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
