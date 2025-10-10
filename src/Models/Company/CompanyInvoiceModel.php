<?php
/**
 * Company Invoice Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyInvoiceModel.php
 *
 * Description: Model untuk mengelola data invoice customer.
 *              Menangani operasi terkait invoice, pembayaran, dan status.
 *              Includes:
 *              - CRUD operations untuk invoice
 *              - Invoice numbering dan generation
 *              - Status management (pending, paid, overdue, cancelled)
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
     * Mark invoice as overdue
     *
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function markAsOverdue(int $id): bool {
        return $this->update($id, ['status' => 'overdue']);
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
            AND status IN ('pending', 'overdue')
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
            AND status IN ('pending', 'overdue')
        ", $customer_id));

        return (float) $total;
    }

    /**
     * Check if invoice is overdue
     *
     * @param int $id Invoice ID
     * @return bool True if overdue
     */
    public function isOverdue(int $id): bool {
        $invoice = $this->find($id);
        if (!$invoice || $invoice->status !== 'pending') {
            return false;
        }

        return strtotime($invoice->due_date) < time();
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
            'paid' => __('Lunas', 'wp-customer'),
            'overdue' => __('Terlambat', 'wp-customer'),
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
        return $this->company_model->find($branch_id);
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
            'order_dir' => 'desc'
        ];
        $params = wp_parse_args($params, $defaults);

        // Base query with JOIN to get company name
        $branches_table = $wpdb->prefix . 'app_customer_branches';

        $base_query = "FROM {$this->table} ci
                      LEFT JOIN {$branches_table} b ON ci.branch_id = b.id";

        $where = " WHERE 1=1";

        // Search
        if (!empty($params['search'])) {
            $search = '%' . $wpdb->esc_like($params['search']) . '%';
            $where .= $wpdb->prepare(" AND (ci.invoice_number LIKE %s OR b.name LIKE %s)", $search, $search);
        }

        // Get total records
        $total = $wpdb->get_var("SELECT COUNT(*) {$base_query} {$where}");

        // Order
        $order = "ORDER BY ci.{$params['order_column']} {$params['order_dir']}";

        // Limit
        $limit = $wpdb->prepare("LIMIT %d, %d", $params['start'], $params['length']);

        // Get data
        $query = "SELECT ci.*, b.name as company_name {$base_query} {$where} {$order} {$limit}";
        $data = $wpdb->get_results($query);

        // Format data for DataTable
        $formatted_data = [];
        foreach ($data as $row) {
            $formatted_data[] = [
                'id' => $row->id,
                'invoice_number' => $row->invoice_number,
                'company_name' => $row->company_name ?? '-',
                'amount' => 'Rp ' . number_format($row->amount, 0, ',', '.'),
                'status' => $this->getStatusLabel($row->status),
                'status_raw' => $row->status,
                'due_date' => date('d/m/Y', strtotime($row->due_date)),
                'created_at' => date('d/m/Y H:i', strtotime($row->created_at)),
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
     * Get invoice statistics for dashboard
     *
     * @return array Statistics data
     */
    public function getStatistics(): array {
        global $wpdb;

        $total_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
        $pending_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'");

        $payments_table = $wpdb->prefix . 'app_customer_payments';
        $total_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$payments_table}");

        return [
            'total_invoices' => (int) $total_invoices,
            'pending_invoices' => (int) $pending_invoices,
            'total_payments' => (int) $total_payments
        ];
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

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$payments_table}
            WHERE invoice_id = %d
            ORDER BY payment_date DESC
        ", $invoice_id));
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

