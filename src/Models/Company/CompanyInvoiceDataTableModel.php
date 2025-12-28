<?php
/**
 * Company Invoice DataTable Model
 *
 * Handles server-side DataTable processing for company invoices.
 * Extends DataTableModel from wp-app-core.
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyInvoiceDataTableModel.php
 *
 * Description: Server-side DataTable model untuk menampilkan daftar
 *              company invoices dengan row click support untuk
 *              wp-datatable DualPanel framework.
 *              Uses CompanyInvoiceModel->getDataTableData() for data fetching.
 *              Includes View button dengan wpdt-panel-trigger class.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation for Company Invoice dashboard
 * - Leverages existing CompanyInvoiceModel->getDataTableData()
 * - View button has wpdt-panel-trigger class for dual panel
 * - Status filters: pending, paid, pending_payment, cancelled
 * - Access-based filtering (admin, customer_admin, branch_admin, employee)
 * - Columns: Invoice #, Company, From Level, To Level, Period, Amount, Status, Due Date, Actions
 */

namespace WPCustomer\Models\Company;

use WPDataTable\Core\AbstractDataTable;
use WPCustomer\Models\Company\CompanyInvoiceModel;
use WPCustomer\Validators\CustomerValidator;

defined('ABSPATH') || exit;

class CompanyInvoiceDataTableModel extends AbstractDataTable {

    /**
     * @var CompanyInvoiceModel
     */
    private $invoice_model;

    /**
     * Table alias for JOINs
     * @var string
     */
    protected $table_alias = 'inv';

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        $this->invoice_model = new CompanyInvoiceModel();

        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_invoices ' . $this->table_alias;
        $this->index_column = $this->table_alias . '.id';

        // Define searchable columns
        $this->searchable_columns = [
            $this->table_alias . '.invoice_number',
            'b.name', // company name
            'ml_from.name', // from level name
            'ml_to.name' // to level name
        ];

        // Base JOINs for company name and level names
        $this->base_joins = [
            "LEFT JOIN {$wpdb->prefix}app_customer_branches b ON {$this->table_alias}.branch_id = b.id",
            "LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id",
            "LEFT JOIN {$wpdb->prefix}app_customer_membership_levels ml_from ON {$this->table_alias}.from_level_id = ml_from.id",
            "LEFT JOIN {$wpdb->prefix}app_customer_membership_levels ml_to ON {$this->table_alias}.level_id = ml_to.id"
        ];

        // Base WHERE for filtering (will be populated by filter_where)
        $this->base_where = [];

        // Hook to add dynamic WHERE conditions
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
            "{$alias}.id as id",
            "{$alias}.invoice_number as invoice_number",
            "b.name as company_name",
            "ml_from.name as from_level_name",
            "ml_to.name as level_name",
            "{$alias}.from_level_id as from_level_id",
            "{$alias}.level_id as level_id",
            "{$alias}.period_months as period_months",
            "{$alias}.amount as amount",
            "{$alias}.status as status",
            "{$alias}.due_date as due_date",
            "{$alias}.paid_date as paid_date",
            "{$alias}.branch_id as branch_id"
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * @param object $row Database row
     * @return array Formatted row data
     */
    public function format_row($row): array {
        // Check if this is an upgrade
        $is_upgrade = ($row->from_level_id && $row->level_id && $row->from_level_id != $row->level_id);

        // Format amount
        $amount_formatted = 'Rp ' . number_format($row->amount ?? 0, 0, ',', '.');

        // Format period
        $period_display = ($row->period_months ?? 0) . ' bulan';

        // Format status badge
        $status_badge = $this->generate_status_badge($row->status ?? 'pending');

        // Format due date
        $due_date_formatted = !empty($row->due_date) ? date('d/m/Y', strtotime($row->due_date)) : '-';

        // Format paid date (if paid)
        $paid_date_formatted = !empty($row->paid_date) ? date('d/m/Y', strtotime($row->paid_date)) : '-';

        return [
            'DT_RowId' => 'invoice-' . ($row->id ?? 0),
            'DT_RowData' => [
                'id' => $row->id ?? 0,
                'branch_id' => $row->branch_id ?? 0,
                'status' => $row->status ?? 'pending',
                'entity' => 'company-invoice',
                'is_upgrade' => $is_upgrade
            ],
            'invoice_number' => esc_html($row->invoice_number ?? ''),
            'company_name' => esc_html($row->company_name ?? '-'),
            'from_level_name' => esc_html($row->from_level_name ?? '-'),
            'level_name' => esc_html($row->level_name ?? '-'),
            'is_upgrade' => $is_upgrade,
            'period_months' => esc_html($period_display),
            'amount' => esc_html($amount_formatted),
            'status' => $status_badge,
            'status_raw' => $row->status ?? 'pending',
            'due_date' => esc_html($due_date_formatted),
            'paid_date' => esc_html($paid_date_formatted),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Generate status badge HTML
     *
     * @param string $status Invoice status
     * @return string HTML badge
     */
    protected function generate_status_badge(string $status): string {
        $badge_classes = [
            'pending' => 'status-pending',
            'pending_payment' => 'status-pending-payment',
            'paid' => 'status-paid',
            'cancelled' => 'status-cancelled'
        ];

        $status_labels = [
            'pending' => __('Belum Dibayar', 'wp-customer'),
            'pending_payment' => __('Menunggu Validasi', 'wp-customer'),
            'paid' => __('Lunas', 'wp-customer'),
            'cancelled' => __('Dibatalkan', 'wp-customer')
        ];

        $badge_class = $badge_classes[$status] ?? 'status-pending';
        $status_text = $status_labels[$status] ?? $status;

        return sprintf(
            '<span class="status-badge %s">%s</span>',
            $badge_class,
            $status_text
        );
    }

    /**
     * Generate action buttons for invoice row
     *
     * Following wp-datatable dual-panel pattern:
     * - View button with wpdt-panel-trigger class (REQUIRED)
     * - Edit/Delete buttons (optional, with custom handlers)
     *
     * TODO: Migrate to ActionButtonHelper when TODO-7107 is implemented
     *
     * @param object $row Invoice data
     * @return string HTML buttons
     */
    protected function generate_action_buttons($row, array $options = []): string {
        $buttons = [];

        // ✅ View button - REQUIRED for panel trigger
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpdt-panel-trigger" data-id="%d" data-entity="company-invoice" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-customer')
        );

        // Edit button (shown for users with edit permission and unpaid invoices)
        if (($row->status === 'pending' || $row->status === 'pending_payment') &&
            (current_user_can('manage_options') || current_user_can('edit_all_customer_branches'))) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small invoice-edit-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit Invoice', 'wp-customer')
            );
        }

        // Cancel button (admin only, for unpaid invoices)
        if (($row->status === 'pending' || $row->status === 'pending_payment') &&
            current_user_can('manage_options')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small invoice-cancel-btn" data-id="%d" title="%s">
                    <span class="dashicons dashicons-no"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Cancel Invoice', 'wp-customer')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Filter WHERE conditions
     *
     * Hooked to: wpapp_datatable_invoice_where
     * Applies access-based filtering and status filters
     *
     * @param array $where_conditions Current WHERE conditions
     * @param array $request_data DataTables request data
     * @param DataTableModel $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where($where_conditions, $request_data, $model): array {
        global $wpdb;
        $alias = $this->table_alias;

        // Get user relation from CustomerValidator to determine access
        $validator = new CustomerValidator();
        $relation = $validator->getUserRelation(0);
        $access_type = $relation['access_type'];

        // Apply access filtering (same as CompanyInvoiceModel->getDataTableData())
        if ($relation['is_admin']) {
            // Administrator - see all invoices
            // No additional restrictions
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all invoices for branches under their customer
            $where_conditions[] = sprintf("c.user_id = %d", get_current_user_id());
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see invoices for their branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_customer_branches
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where_conditions[] = sprintf("{$alias}.branch_id = %d", $branch_id);
            } else {
                $where_conditions[] = "1=0"; // No branch found
            }
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see invoices for the branch they work in
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where_conditions[] = sprintf("{$alias}.branch_id = %d", $employee_branch);
            } else {
                $where_conditions[] = "1=0"; // No branch found
            }
        }
        else {
            // No access
            $where_conditions[] = "1=0";
        }

        // Status filters (from request data)
        $status_filters = [];
        if (!empty($request_data['filter_pending'])) {
            $status_filters[] = 'pending';
        }
        if (!empty($request_data['filter_paid'])) {
            $status_filters[] = 'paid';
        }
        if (!empty($request_data['filter_pending_payment'])) {
            $status_filters[] = 'pending_payment';
        }
        if (!empty($request_data['filter_cancelled'])) {
            $status_filters[] = 'cancelled';
        }

        // If no status selected, default to pending
        if (empty($status_filters)) {
            $status_filters[] = 'pending';
        }

        // Build IN clause for selected statuses
        $status_placeholders = implode(', ', array_map(function($status) {
            return "'" . esc_sql($status) . "'";
        }, $status_filters));

        $where_conditions[] = "{$alias}.status IN ($status_placeholders)";

        return $where_conditions;
    }

    /**
     * Get table alias
     *
     * @return string Table alias for JOIN operations
     */
    public function get_table_alias(): string {
        return $this->table_alias;
    }

    /**
     * Get DataTable data
     *
     * Overrides parent method to use CompanyInvoiceModel->getDataTableData()
     * which already includes access filtering and formatting
     *
     * @param mixed $request_data DataTables request data
     * @return array Response data for DataTables
     */
    public function get_datatable_data($request_data) {
        $request = (array) $request_data;
        // Map DataTables request to CompanyInvoiceModel parameters
        $params = [
            'start' => isset($request['start']) ? intval($request['start']) : 0,
            'length' => isset($request['length']) ? intval($request['length']) : 10,
            'search' => isset($request['search']['value']) ? sanitize_text_field($request['search']['value']) : '',
            'order_column' => 'created_at',
            'order_dir' => 'desc',
            'filter_pending' => !empty($request['filter_pending']) ? 1 : 0,
            'filter_paid' => !empty($request['filter_paid']) ? 1 : 0,
            'filter_pending_payment' => !empty($request['filter_pending_payment']) ? 1 : 0,
            'filter_cancelled' => !empty($request['filter_cancelled']) ? 1 : 0
        ];

        // Handle ordering
        if (isset($request['order'][0]['column']) && isset($request['columns'][$request['order'][0]['column']]['data'])) {
            $order_column = sanitize_text_field($request['columns'][$request['order'][0]['column']]['data']);
            $order_dir = isset($request['order'][0]['dir']) && $request['order'][0]['dir'] === 'asc' ? 'asc' : 'desc';

            // Map column names to database columns
            $column_map = [
                'invoice_number' => 'invoice_number',
                'company_name' => 'company_name',
                'amount' => 'amount',
                'due_date' => 'due_date',
                'status' => 'status'
            ];

            if (isset($column_map[$order_column])) {
                $params['order_column'] = $column_map[$order_column];
                $params['order_dir'] = $order_dir;
            }
        }

        // Get data from CompanyInvoiceModel
        $result = $this->invoice_model->getDataTableData($params);

        // Override actions column for each row (Model returns empty actions)
        // We need to add DualPanel-compatible action buttons here
        foreach ($result['data'] as &$row) {
            // Create a mock row object for generate_action_buttons()
            $row_obj = (object) [
                'id' => $row['id'] ?? 0,
                'status' => $row['status_raw'] ?? 'pending'
            ];

            // Generate action buttons
            $row['actions'] = $this->generate_action_buttons($row_obj);

            // Add DT_RowData for panel-manager.js row click
            $row['DT_RowData'] = [
                'id' => $row['id'] ?? 0,
                'status' => $row['status_raw'] ?? 'pending',
                'entity' => 'company-invoice'
            ];
        }

        // Format for DataTables response
        return [
            'draw' => isset($request['draw']) ? intval($request['draw']) : 1,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['data']
        ];
    }
}
