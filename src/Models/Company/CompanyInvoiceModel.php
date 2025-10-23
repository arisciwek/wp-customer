<?php
/**
 * Company Invoice Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyInvoiceModel.php
 *
 * Description: Model untuk mengelola data invoice customer.
 *              Menangani operasi terkait invoice, pembayaran, dan status.
 *              Includes:
 *              - CRUD operations untuk invoice
 *              - Invoice numbering dan generation
 *              - Status management (pending, paid, pending_payment, cancelled)
 *              - Payment tracking dan linking
 *              - Cache management untuk optimasi performa
 *
 * Dependencies:
 * - WPCustomer\Cache\CustomerCacheManager
 * - WPCustomer\Models\Customer\CustomerModel
 * - WPCustomer\Models\Company\CompanyModel
 * - WordPress $wpdb
 *
 * Changelog:
 * 1.0.3 - 2025-10-18 (Review-03)
 * - Changed: Removed status 'overdue', added status 'pending_payment'
 * - Updated: getStatusLabel() - replaced 'Terlambat' with 'Menunggu Validasi'
 * - Renamed: markAsOverdue() → markAsPendingPayment()
 * - Renamed: isOverdue() → isPendingPayment()
 * - Updated: getUnpaidAmount() and getTotalUnpaidAmount() - use 'pending_payment' instead of 'overdue'
 * - Updated: getDataTableData() - filter parameter 'filter_overdue' → 'filter_pending_payment'
 * - Reason: System tidak ada auto-payment, ada gratis membership sebagai fallback
 * - New flow: pending → pending_payment (after upload proof) → paid (after validation)
 *
 * 1.0.2 - 2025-01-17 (Review-02)
 * - Fixed: getInvoicePayments() query error - column invoice_id tidak ada di tabel
 * - Changed: Query menggunakan metadata LIKE search karena invoice_id tersimpan di JSON metadata
 * - Invoice ID stored as: {"invoice_id":4,...} dalam metadata field
 *
 * 1.0.1 - 2025-01-17 (Review-01)
 * - Fixed: getStatistics() sekarang menggunakan access-based filtering
 * - Added: Access filtering untuk admin, customer_admin, customer_branch_admin, customer_employee
 * - Admin: lihat statistik invoice semua customer
 * - Customer Admin: lihat statistik invoice customer miliknya dan cabang dibawahnya
 * - Customer Branch Admin: lihat statistik invoice untuk cabangnya saja
 * - Customer Employee: lihat statistik invoice untuk cabangnya saja
 *
 * 1.0.0 - 2024-10-08
 * - Initial version
 * - Added core invoice operations
 * - Added invoice numbering
 * - Added status management
 * - Added payment tracking
 */

namespace WPCustomer\Models\Company;

use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Company\CompanyModel;

class CompanyInvoiceModel {
    /**
     * Database table name
     * @var string
     */
    private $table;

    /**
     * Cache manager instance
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Customer model instance
     * @var CustomerModel
     */
    private $customer_model;

    /**
     * Company model instance
     * @var CompanyModel
     */
    private $company_model;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_invoices';
        $this->cache = new CustomerCacheManager();
        $this->customer_model = new CustomerModel();
        $this->company_model = new CompanyModel();
    }

    /**
     * Find invoice by ID
     *
     * @param int $id Invoice ID
     * @return object|null Invoice data or null
     */
    public function find(int $id) {
        global $wpdb;

        $cache_key = "invoice_{$id}";
        $cached = $this->cache->get('invoice', $cache_key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table} WHERE id = %d
        ", $id));

        if ($result) {
            $this->cache->set('invoice', $result, 300, $cache_key); // Cache for 5 minutes
        }

        return $result;
    }

    /**
     * Find invoices by customer ID
     *
     * @param int $customer_id Customer ID
     * @param array $args Additional query arguments
     * @return array Array of invoice objects
     */
    public function findByCustomer(int $customer_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'status' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE customer_id = %d";
        $params = [$customer_id];

        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }

        $order = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);

        $query = $wpdb->prepare("
            SELECT * FROM {$this->table}
            {$where} {$order} {$limit}
        ", $params);

        return $wpdb->get_results($query);
    }

    /**
     * Find invoices by branch ID
     *
     * @param int $branch_id Branch ID
     * @param array $args Additional query arguments
     * @return array Array of invoice objects
     */
    public function findByBranch(int $branch_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'status' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE branch_id = %d";
        $params = [$branch_id];

        if (!empty($args['status'])) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }

        $order = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);

        $query = $wpdb->prepare("
            SELECT * FROM {$this->table}
            {$where} {$order} {$limit}
        ", $params);

        return $wpdb->get_results($query);
    }

    /**
     * Create new invoice
     *
     * @param array $data Invoice data
     * @return int|false New invoice ID or false on failure
     */
    public function create(array $data) {
        global $wpdb;

        // Set default values
        $data = wp_parse_args($data, [
            'status' => 'pending',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);

        // Generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
        }

        // Validate required fields
        if (empty($data['customer_id']) || empty($data['amount']) || empty($data['due_date'])) {
            return false;
        }

        // Insert to database
        $result = $wpdb->insert($this->table, $data);
        if ($result === false) {
            return false;
        }

        $new_id = $wpdb->insert_id;

        // Clear related caches
        $this->clearCache($new_id);
        if (!empty($data['customer_id'])) {
            $this->cache->delete('customer_invoices', $data['customer_id']);
            $this->cache->delete('customer_unpaid_invoice_count', $data['customer_id']);
        }

        return $new_id;
    }

    /**
     * Update invoice
     *
     * @param int $id Invoice ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        // Add updated timestamp
        $data['updated_at'] = current_time('mysql');

        // Update database
        $result = $wpdb->update(
            $this->table,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            $this->clearCache($id);

            // Get invoice to clear customer cache
            $invoice = $this->find($id);
            if ($invoice) {
                $this->cache->delete('customer_invoices', $invoice->customer_id);
                $this->cache->delete('customer_unpaid_invoice_count', $invoice->customer_id);
            }

            return true;
        }

        return false;
    }

    /**
     * Delete invoice
     *
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function delete(int $id): bool {
        global $wpdb;

        // Get invoice before deletion for cache clearing
        $invoice = $this->find($id);
        if (!$invoice) {
            return false;
        }

        $result = $wpdb->delete($this->table, ['id' => $id]);

        if ($result !== false) {
            $this->clearCache($id);
            $this->cache->delete('customer_invoices', $invoice->customer_id);
            $this->cache->delete('customer_unpaid_invoice_count', $invoice->customer_id);
            return true;
        }

        return false;
    }

    /**
     * Mark invoice as paid
     *
     * @param int $id Invoice ID
     * @param string $payment_date Payment date (optional)
     * @return bool Success status
     */
    public function markAsPaid(int $id, string $payment_date = ''): bool {
        $data = [
            'status' => 'paid',
            'paid_date' => $payment_date ?: current_time('mysql')
        ];

        return $this->update($id, $data);
    }

    /**
     * Mark invoice as pending payment (uploaded proof, waiting validation)
     *
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function markAsPendingPayment(int $id): bool {
        return $this->update($id, ['status' => 'pending_payment']);
    }

    /**
     * Cancel invoice
     *
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function cancel(int $id): bool {
        return $this->update($id, ['status' => 'cancelled']);
    }

    /**
     * Generate unique invoice number
     *
     * @return string Invoice number
     */
    public function generateInvoiceNumber(): string {
        global $wpdb;

        $date = date('Ym');
        $prefix = 'INV-' . $date . '-';

        // Find the highest number for this month
        $last_number = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED))
            FROM {$this->table}
            WHERE invoice_number LIKE %s
        ", $prefix . '%'));

        $next_number = $last_number ? $last_number + 1 : 1;

        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get unpaid invoices for customer
     *
     * @param int $customer_id Customer ID
     * @return array Array of unpaid invoice objects
     */
    public function getUnpaidInvoices(int $customer_id): array {
        return $this->findByCustomer($customer_id, [
            'status' => 'pending'
        ]);
    }

    /**
     * Get unpaid invoice count for customer
     *
     * @param int $customer_id Customer ID
     * @return int Number of unpaid invoices
     */
    public function getUnpaidInvoiceCount(int $customer_id): int {
        global $wpdb;

        // Check cache first
        $cached_count = $this->cache->get('customer_unpaid_invoice_count', $customer_id);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$this->table}
            WHERE customer_id = %d
            AND status IN ('pending', 'pending_payment')
        ", $customer_id));

        // Cache for 5 minutes
        $this->cache->set('customer_unpaid_invoice_count', $count, 300, $customer_id);

        return $count;
    }

    /**
     * Get total amount of unpaid invoices for customer
     *
     * @param int $customer_id Customer ID
     * @return float Total unpaid amount
     */
    public function getTotalUnpaidAmount(int $customer_id): float {
        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(amount)
            FROM {$this->table}
            WHERE customer_id = %d
            AND status IN ('pending', 'pending_payment')
        ", $customer_id));

        return (float) $total;
    }

    /**
     * Check if invoice is pending payment validation
     *
     * @param int $id Invoice ID
     * @return bool True if pending payment
     */
    public function isPendingPayment(int $id): bool {
        $invoice = $this->find($id);
        return $invoice && $invoice->status === 'pending_payment';
    }

    /**
     * Get invoice status label
     *
     * @param string $status Status code
     * @return string Status label in Indonesian
     */
    public function getStatusLabel(string $status): string {
        $labels = [
            'pending' => __('Belum Dibayar', 'wp-customer'),
            'pending_payment' => __('Menunggu Validasi', 'wp-customer'),
            'paid' => __('Lunas', 'wp-customer'),
            'cancelled' => __('Dibatalkan', 'wp-customer')
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Clear invoice cache
     *
     * @param int $id Invoice ID
     */
    private function clearCache(int $id): void {
        $this->cache->delete('invoice', "invoice_{$id}");
    }

    /**
     * Get customer data by customer ID
     *
     * @param int $customer_id Customer ID
     * @return object|null Customer data or null
     */
    public function getCustomerData(int $customer_id) {
        return $this->customer_model->find($customer_id);
    }

    /**
     * Get branch data by branch ID
     *
     * @param int $branch_id Branch ID
     * @return object|null Branch data or null
     */
    public function getBranchData(int $branch_id) {
        // CompanyModel doesn't have find() method, use getBranchWithLatestMembership() instead
        return $this->company_model->getBranchWithLatestMembership($branch_id);
    }

    /**
     * Get DataTable data for company invoice listing
     *
     * @param array $params DataTable parameters
     * @return array DataTable formatted data
     */
    public function getDataTableData(array $params = []): array {
        global $wpdb;

        $defaults = [
            'start' => 0,
            'length' => 10,
            'search' => '',
            'order_column' => 'created_at',
            'order_dir' => 'desc',
            'filter_pending' => 1,
            'filter_paid' => 0,
            'filter_pending_payment' => 0,
            'filter_cancelled' => 0
        ];
        $params = wp_parse_args($params, $defaults);

        error_log('=== Debug CompanyInvoiceModel getDataTableData ===');
        error_log('User ID: ' . get_current_user_id());

        // Get user relation from CustomerModel to determine access
        $relation = $this->customer_model->getUserRelation(0);
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);

        // Base query with JOIN to get company name and both level names
        $branches_table = $wpdb->prefix . 'app_customer_branches';
        $customers_table = $wpdb->prefix . 'app_customers';
        $levels_table = $wpdb->prefix . 'app_customer_membership_levels';

        $base_query = "FROM {$this->table} ci
                      LEFT JOIN {$branches_table} b ON ci.branch_id = b.id
                      LEFT JOIN {$customers_table} c ON b.customer_id = c.id
                      LEFT JOIN {$levels_table} ml_from ON ci.from_level_id = ml_from.id
                      LEFT JOIN {$levels_table} ml_to ON ci.level_id = ml_to.id";

        $where = " WHERE 1=1";
        $where_params = [];

        // Apply access filtering
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        if ($relation['is_admin']) {
            // Administrator - see all invoices
            error_log('User is admin - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all invoices for branches under their customer
            $where .= " AND c.user_id = %d";
            $where_params[] = get_current_user_id();
            error_log('Added customer admin restriction');
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see invoices for their branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$branches_table}
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where .= " AND ci.branch_id = %d";
                $where_params[] = $branch_id;
                error_log('Added customer branch admin restriction for branch: ' . $branch_id);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Customer branch admin has no branch - blocking access');
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
                $where .= " AND ci.branch_id = %d";
                $where_params[] = $employee_branch;
                error_log('Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
        }

        // Apply extensibility filter
        $where = apply_filters('wp_company_membership_invoice_datatable_where', $where, $access_type, $relation, $where_params);

        // Prepare WHERE clause with params
        if (!empty($where_params)) {
            $where_prepared = $wpdb->prepare($where, $where_params);
        } else {
            $where_prepared = $where;
        }

        // Search
        if (!empty($params['search'])) {
            $search = '%' . $wpdb->esc_like($params['search']) . '%';
            $search_clause = $wpdb->prepare(" AND (ci.invoice_number LIKE %s OR b.name LIKE %s OR ml_from.name LIKE %s OR ml_to.name LIKE %s)", $search, $search, $search, $search);
            $where_prepared .= $search_clause;
        }

        // Payment Status Filter
        $status_filters = [];
        if (!empty($params['filter_pending'])) {
            $status_filters[] = 'pending';
        }
        if (!empty($params['filter_paid'])) {
            $status_filters[] = 'paid';
        }
        if (!empty($params['filter_pending_payment'])) {
            $status_filters[] = 'pending_payment';
        }
        if (!empty($params['filter_cancelled'])) {
            $status_filters[] = 'cancelled';
        }

        // If no status selected, show nothing
        if (empty($status_filters)) {
            $where_prepared .= " AND 1=0";
        } else {
            // Build IN clause for selected statuses
            $status_placeholders = implode(', ', array_fill(0, count($status_filters), '%s'));
            $status_clause = $wpdb->prepare(" AND ci.status IN ($status_placeholders)", $status_filters);
            $where_prepared .= $status_clause;
        }

        error_log('Status filters: ' . print_r($status_filters, true));
        error_log('Final WHERE: ' . $where_prepared);

        // Get total records
        $total = $wpdb->get_var("SELECT COUNT(*) {$base_query} {$where_prepared}");

        // Order
        $order = "ORDER BY ci.{$params['order_column']} {$params['order_dir']}";

        // Limit
        $limit = $wpdb->prepare("LIMIT %d, %d", $params['start'], $params['length']);

        // Get data with both level names
        $query = "SELECT ci.*,
                         b.name as company_name,
                         ml_from.name as from_level_name,
                         ml_to.name as to_level_name
                  {$base_query} {$where_prepared} {$order} {$limit}";

        error_log('Final Query: ' . $query);
        $data = $wpdb->get_results($query);
        error_log('Total records: ' . count($data));
        error_log('=== End Debug ===');

        // Format data for DataTable
        $formatted_data = [];
        foreach ($data as $row) {
            $formatted_data[] = [
                'id' => $row->id,
                'invoice_number' => $row->invoice_number,
                'company_name' => $row->company_name ?? '-',
                'from_level_name' => $row->from_level_name ?? '-',
                'level_name' => $row->to_level_name ?? '-',
                'is_upgrade' => ($row->from_level_id && $row->level_id && $row->from_level_id != $row->level_id),
                'period_months' => $row->period_months . ' bulan',
                'amount' => 'Rp ' . number_format($row->amount, 0, ',', '.'),
                'status' => $this->getStatusLabel($row->status),
                'status_raw' => $row->status,
                'due_date' => date('d/m/Y', strtotime($row->due_date)),
                'actions' => ''
            ];
        }

        return [
            'total' => (int) $total,
            'filtered' => (int) $total,
            'data' => $formatted_data
        ];
    }

    /**
     * Get invoice statistics for dashboard with access-based filtering
     *
     * @return array Statistics data
     */
    public function getStatistics(): array {
        global $wpdb;

        error_log('=== Debug CompanyInvoiceModel getStatistics ===');
        error_log('User ID: ' . get_current_user_id());

        // Get user relation from CustomerModel to determine access
        $relation = $this->customer_model->getUserRelation(0);
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);

        // Build base query with JOIN for access filtering
        $branches_table = $wpdb->prefix . 'app_customer_branches';
        $customers_table = $wpdb->prefix . 'app_customers';

        $from = " FROM {$this->table} ci
                  LEFT JOIN {$branches_table} b ON ci.branch_id = b.id
                  LEFT JOIN {$customers_table} c ON b.customer_id = c.id";

        $where = " WHERE 1=1";
        $where_params = [];

        // Apply access filtering (same as getTotalCount and getDataTableData)
        if ($relation['is_admin']) {
            // Administrator - see all invoices
            error_log('User is admin - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all invoices for branches under their customer
            $where .= " AND c.user_id = %d";
            $where_params[] = get_current_user_id();
            error_log('Added customer admin restriction');
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see invoices for their branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$branches_table}
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where .= " AND ci.branch_id = %d";
                $where_params[] = $branch_id;
                error_log('Added customer branch admin restriction for branch: ' . $branch_id);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Customer branch admin has no branch - blocking access');
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
                $where .= " AND ci.branch_id = %d";
                $where_params[] = $employee_branch;
                error_log('Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
        }

        // Prepare WHERE clause with params
        if (!empty($where_params)) {
            $where_prepared = $wpdb->prepare($where, $where_params);
        } else {
            $where_prepared = $where;
        }

        error_log('Final WHERE: ' . $where_prepared);

        // Get statistics with access filtering
        $total_invoices = $wpdb->get_var("SELECT COUNT(*) {$from} {$where_prepared}");
        $pending_invoices = $wpdb->get_var("SELECT COUNT(*) {$from} {$where_prepared} AND ci.status = 'pending'");
        $paid_invoices = $wpdb->get_var("SELECT COUNT(*) {$from} {$where_prepared} AND ci.status = 'paid'");
        $total_paid_amount = $wpdb->get_var("SELECT SUM(ci.amount) {$from} {$where_prepared} AND ci.status = 'paid'");

        error_log('Total invoices: ' . $total_invoices);
        error_log('Pending: ' . $pending_invoices);
        error_log('Paid: ' . $paid_invoices);
        error_log('Total paid amount: ' . $total_paid_amount);
        error_log('=== End Debug ===');

        return [
            'total_invoices' => (int) $total_invoices,
            'pending_invoices' => (int) $pending_invoices,
            'paid_invoices' => (int) $paid_invoices,
            'total_paid_amount' => (float) ($total_paid_amount ?? 0)
        ];
    }

    /**
     * Get total invoice count based on user permission with access_type filtering
     *
     * @return int Total number of invoices
     */
    public function getTotalCount(): int {
        global $wpdb;

        error_log('=== Debug CompanyInvoiceModel getTotalCount ===');
        error_log('User ID: ' . get_current_user_id());

        // Get user relation from CustomerModel to determine access
        $relation = $this->customer_model->getUserRelation(0);
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);
        error_log('Is admin: ' . ($relation['is_admin'] ? 'yes' : 'no'));
        error_log('Is customer admin: ' . ($relation['is_customer_admin'] ? 'yes' : 'no'));
        error_log('Is customer branch admin: ' . ($relation['is_customer_branch_admin'] ? 'yes' : 'no'));
        error_log('Is employee: ' . ($relation['is_customer_employee'] ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS ci.*";
        $from = " FROM {$this->table} ci";
        $join = " LEFT JOIN {$wpdb->prefix}app_customer_branches b ON ci.branch_id = b.id
                  LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id";

        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all invoices
            error_log('User is admin - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all invoices for branches under their customer
            $where .= " AND c.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added customer admin restriction: ' . $where);
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see invoices for their branch
            $branch_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}app_customer_branches
                 WHERE user_id = %d LIMIT 1",
                get_current_user_id()
            ));

            if ($branch_id) {
                $where .= " AND ci.branch_id = %d";
                $params[] = $branch_id;
                error_log('Added customer branch admin restriction for branch: ' . $branch_id);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Customer branch admin has no branch - blocking access');
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
                $where .= " AND ci.branch_id = %d";
                $params[] = $employee_branch;
                error_log('Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
        }

        // Apply filter for extensibility
        $where = apply_filters('wp_company_membership_invoice_total_count_where', $where, $access_type, $relation, $params);

        // Complete query
        $query = $select . $from . $join . $where;
        $final_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        error_log('Final Query: ' . $final_query);

        // Execute query
        $wpdb->get_results($final_query);

        // Get total and log
        $total = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Total count result: ' . $total);
        error_log('=== End Debug ===');

        return $total;
    }

    /**
     * Get invoice payments
     *
     * @param int $invoice_id Invoice ID
     * @return array Array of payment objects
     */
    public function getInvoicePayments(int $invoice_id): array {
        global $wpdb;
        $payments_table = $wpdb->prefix . 'app_customer_payments';

        // Invoice ID is stored in metadata JSON field, not as separate column
        // Use specific pattern with comma or closing brace to avoid partial matches
        // Pattern: "invoice_id":123, or "invoice_id":123}
        $pattern1 = '%"invoice_id":' . $invoice_id . ',%';
        $pattern2 = '%"invoice_id":' . $invoice_id . '}%';

        // DEBUG: Log query details
        error_log("[DEBUG Review-03 Model] Invoice ID: {$invoice_id}");
        error_log("[DEBUG Review-03 Model] Pattern 1: {$pattern1}");
        error_log("[DEBUG Review-03 Model] Pattern 2: {$pattern2}");
        error_log("[DEBUG Review-03 Model] Payments table: {$payments_table}");

        $query = $wpdb->prepare("
            SELECT * FROM {$payments_table}
            WHERE metadata LIKE %s OR metadata LIKE %s
            ORDER BY created_at DESC
        ", $pattern1, $pattern2);

        // DEBUG: Log prepared query
        error_log("[DEBUG Review-03 Model] Prepared Query: {$query}");

        $results = $wpdb->get_results($query);

        // DEBUG: Log results
        error_log("[DEBUG Review-03 Model] Results count: " . count($results));
        if ($results) {
            foreach ($results as $index => $result) {
                error_log("[DEBUG Review-03 Model] Result {$index}: " . json_encode($result));
            }
        } else {
            error_log("[DEBUG Review-03 Model] No results found");
        }

        return $results;
    }

    /**
     * Get invoice company/branch data
     *
     * @param int $invoice_id Invoice ID
     * @return object|null Company data
     */
    public function getInvoiceCompany(int $invoice_id) {
        global $wpdb;
        $branches_table = $wpdb->prefix . 'app_customer_branches';

        return $wpdb->get_row($wpdb->prepare("
            SELECT b.* FROM {$this->table} ci
            LEFT JOIN {$branches_table} b ON ci.branch_id = b.id
            WHERE ci.id = %d
        ", $invoice_id));
    }
}

