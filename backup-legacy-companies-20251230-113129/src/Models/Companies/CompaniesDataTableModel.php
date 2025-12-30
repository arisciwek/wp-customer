<?php
/**
 * Companies DataTable Model
 *
 * DataTable model for companies (branches) management
 * Uses new pattern from wp-app-core DataTableModel
 *
 * @package WPCustomer
 * @subpackage Models\Companies
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Models\Companies;

use WPDataTable\Core\AbstractDataTable;

/**
 * CompaniesDataTableModel class
 *
 * Extends wp-app-core DataTableModel for companies (branches) listing
 * Table: wp_app_customer_branches
 *
 * @since 1.1.0
 */
class CompaniesDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     * Setup table, columns, joins, and searchable columns
     */
    public function __construct() {
        parent::__construct();

        // Set table name
        $this->table = $this->wpdb->prefix . 'app_customer_branches';

        // Define columns to select
        // IMPORTANT: Array index must match JavaScript column index for sorting!
        // JavaScript columns are 0-6, so we need exactly 7 items here
        $this->columns = [
            // Index 0: ID (hidden)
            $this->table . '.id',

            // Index 1: Code
            $this->table . '.code',

            // Index 2: Name (Company name)
            $this->table . '.name',

            // Index 3: Disnaker name (was Agency at index 5)
            'a.name as agency_name',

            // Index 4: Contact (Phone + Email)
            $this->table . '.phone',

            // Index 5: Address
            $this->table . '.address',

            // Index 6: Actions (generated in format_row)
            $this->table . '.id as id2',

            // Additional fields needed by format_row but not displayed as columns
            $this->table . '.email',
        ];

        // Define searchable columns
        $this->searchable_columns = [
            $this->table . '.code',
            $this->table . '.name',
            $this->table . '.address',
            $this->table . '.phone',
            $this->table . '.email',
            'c.name', // Customer name searchable
            'a.name', // Agency name searchable
        ];

        // Set primary key
        $this->index_column = $this->table . '.id';

        // Add base JOINs for customer and agency
        $this->base_joins = [
            "LEFT JOIN {$this->wpdb->prefix}app_customers c ON c.id = {$this->table}.customer_id",
            "LEFT JOIN {$this->wpdb->prefix}app_agencies a ON a.id = {$this->table}.agency_id",
        ];

        // Optional: Add base WHERE to exclude soft-deleted records
        // $this->base_where = [
        //     "{$this->table}.deleted_at IS NULL"
        // ];
    }

    /**
     * Get DataTable data with custom column handling for aliases
     *
     * Handles ORDER BY for columns with aliases by:
     * 1. Checking which column is being sorted
     * 2. If it has an alias, adding alias-only version at the end
     * 3. Redirecting ORDER BY to use the alias-only column
     * 4. Keeping original columns intact for SELECT
     *
     * @param array $request_data Request data from DataTables
     * @return array DataTable response
     */
    public function get_datatable_data($request_data) {
        // Handle status filter
        // Users without 'edit_all_customers' permission can ONLY see active companies
        if (!current_user_can('edit_all_customers')) {
            // Force status = active for regular users
            if (!isset($this->base_where)) {
                $this->base_where = [];
            }
            $this->base_where[] = $this->wpdb->prepare(
                "{$this->table}.status = %s",
                'active'
            );
        } elseif (!empty($request_data['status'])) {
            // Users with permission can filter by status
            $status = sanitize_text_field($request_data['status']);
            if (in_array($status, ['active', 'inactive'])) {
                // Add status filter to base WHERE conditions
                if (!isset($this->base_where)) {
                    $this->base_where = [];
                }
                $this->base_where[] = $this->wpdb->prepare(
                    "{$this->table}.status = %s",
                    $status
                );
            }
        }

        // Check if there's an order request
        if (!empty($request_data['order'])) {
            $order_column_index = intval($request_data['order'][0]['column']);

            // Check if this column has an alias
            if (isset($this->columns[$order_column_index])) {
                $column_def = $this->columns[$order_column_index];

                // If column has "AS alias", we need to add sortable version
                if (stripos($column_def, ' as ') !== false) {
                    // Extract source column: "c.name as customer_name" -> "c.name"
                    $parts = preg_split('/\s+as\s+/i', $column_def);
                    $source_column = trim($parts[0]);

                    // Add source column at the end (for ORDER BY to use)
                    // This allows ORDER BY to use the actual column, not the alias
                    $temp_column_index = count($this->columns);
                    $this->columns[] = $source_column;

                    // Update request to order by the new column index
                    $request_data['order'][0]['column'] = $temp_column_index;

                    // Call parent with modified request
                    $result = parent::get_datatable_data($request_data);

                    // Remove temporary column
                    array_pop($this->columns);

                    return $result;
                }
            }
        }

        // No alias in ORDER BY, call parent normally
        return parent::get_datatable_data($request_data);
    }

    /**
     * Format row for DataTable output
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    public function format_row($row) {
        return [
            // Column 0: ID (hidden, for reference)
            $row->id,

            // Column 1: Code with link
            sprintf(
                '<a href="%s" class="company-code-link">%s</a>',
                admin_url('admin.php?page=wp-customer-companies&action=view&id=' . $row->id),
                esc_html($row->code)
            ),

            // Column 2: Company Name with link
            sprintf(
                '<a href="%s" class="company-name-link"><strong>%s</strong></a>',
                admin_url('admin.php?page=wp-customer-companies&action=view&id=' . $row->id),
                esc_html($row->name)
            ),

            // Column 3: Disnaker name
            esc_html($row->agency_name ?: '-'),

            // Column 4: Contact info (phone & email)
            $this->get_contact_info($row),

            // Column 5: Address (truncated)
            $this->get_truncated_address($row->address),

            // Column 6: Actions
            $this->get_action_buttons($row)
        ];
    }

    /**
     * Get type badge HTML
     *
     * @param string $type Type value (pusat/cabang)
     * @return string HTML badge
     */
    private function get_type_badge($type) {
        $badges = [
            'pusat' => '<span class="badge badge-primary"><i class="dashicons dashicons-building"></i> Pusat</span>',
            'cabang' => '<span class="badge badge-secondary"><i class="dashicons dashicons-store"></i> Cabang</span>',
        ];

        return $badges[$type] ?? '<span class="badge badge-secondary">' . esc_html($type) . '</span>';
    }

    /**
     * Get status badge HTML
     *
     * @param string $status Status value (active/inactive)
     * @return string HTML badge
     */
    private function get_status_badge($status) {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-danger">Inactive</span>',
        ];

        return $badges[$status] ?? '<span class="badge badge-secondary">' . esc_html($status) . '</span>';
    }

    /**
     * Get contact info HTML
     *
     * @param object $row Row object
     * @return string HTML contact info
     */
    private function get_contact_info($row) {
        $contact = [];

        if (!empty($row->phone)) {
            $contact[] = '<i class="dashicons dashicons-phone"></i> ' . esc_html($row->phone);
        }

        if (!empty($row->email)) {
            $contact[] = '<i class="dashicons dashicons-email"></i> ' . esc_html($row->email);
        }

        if (empty($contact)) {
            return '-';
        }

        return '<div class="contact-info">' . implode('<br>', $contact) . '</div>';
    }

    /**
     * Get truncated address
     *
     * @param string $address Full address
     * @return string Truncated address with tooltip
     */
    private function get_truncated_address($address) {
        if (empty($address)) {
            return '-';
        }

        $max_length = 50;
        $truncated = strlen($address) > $max_length
            ? substr($address, 0, $max_length) . '...'
            : $address;

        return sprintf(
            '<span class="address-truncated" title="%s">%s</span>',
            esc_attr($address),
            esc_html($truncated)
        );
    }

    /**
     * Get action buttons HTML
     *
     * @param object $row Row object
     * @return string HTML action buttons
     */
    private function get_action_buttons($row) {
        $actions = [];

        // Check permissions via filters
        /**
         * Filter: wp_customer_can_view_company
         *
         * @param bool $can_view Default permission
         * @param int $company_id Company ID
         */
        $can_view = apply_filters('wp_customer_can_view_company', current_user_can('view_customer_branch_detail'), $row->id);

        /**
         * Filter: wp_customer_can_edit_company
         *
         * @param bool $can_edit Default permission
         * @param int $company_id Company ID
         */
        $can_edit = apply_filters('wp_customer_can_edit_company', current_user_can('edit_all_customer_branches'), $row->id);

        /**
         * Filter: wp_customer_can_delete_company
         *
         * @param bool $can_delete Default permission
         * @param int $company_id Company ID
         */
        $can_delete = apply_filters('wp_customer_can_delete_company', current_user_can('delete_customer_branch'), $row->id);

        // View button
        if ($can_view) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-info" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </a>',
                admin_url('admin.php?page=wp-customer-companies&action=view&id=' . $row->id),
                __('View', 'wp-customer')
            );
        }

        // Edit button
        if ($can_edit) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-primary" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </a>',
                admin_url('admin.php?page=wp-customer-companies&action=edit&id=' . $row->id),
                __('Edit', 'wp-customer')
            );
        }

        // Delete button
        if ($can_delete) {
            $actions[] = sprintf(
                '<a href="#" class="btn btn-sm btn-danger delete-company"
                   data-id="%d"
                   data-name="%s"
                   title="%s">
                    <i class="dashicons dashicons-trash"></i>
                </a>',
                $row->id,
                esc_attr($row->name),
                __('Delete', 'wp-customer')
            );
        }

        if (empty($actions)) {
            return '-';
        }

        return '<div class="btn-group">' . implode('', $actions) . '</div>';
    }
}
