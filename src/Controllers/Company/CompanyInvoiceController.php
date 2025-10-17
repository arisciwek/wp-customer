<?php
/**
 * Company Invoice Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.0.4
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyInvoiceController.php
 *
 * Description: Controller untuk mengelola operasi terkait invoice customer:
 *              - View invoice list dan details
 *              - Create, update, delete invoices
 *              - Mark invoices as paid
 *              - Invoice status management
 *              Includes permission validation dan error handling.
 *
 * Changelog:
 * 1.0.4 - 2025-10-17 (Review-08)
 * - Fixed: payment button visibility menggunakan can_pay flag dari formatInvoiceData()
 * - Fixed: CustomerCacheManager::flush() diganti dengan clearCache()
 * - Updated: payment capabilities menggunakan "membership" terminology
 * - Changed: pay_all_customer_invoices → pay_all_customer_membership_invoices
 * - Changed: pay_own_customer_invoices → pay_own_customer_membership_invoices
 * - Changed: pay_own_branch_invoices → pay_own_branch_membership_invoices
 *
 * 1.0.3 - 2025-01-17 (Review-05)
 * - Added: Role-based payment button access menggunakan validator->canPayInvoice()
 * - Fixed: handle_invoice_payment() sekarang menggunakan validator untuk access control
 * - Supports: customer_admin (all branches), customer_branch_admin (own branch only)
 * - Removed: Hardcoded manage_options check untuk payment
 *
 * 1.0.2 - 2025-01-17 (Review-03)
 * - Fixed: getCompanyInvoicePayments() sekarang memformat payment data dengan benar
 * - Fixed: Ekstrak metadata JSON untuk mendapatkan payment_date yang sebenarnya
 * - Fixed: Map description field ke notes untuk kompatibilitas dengan JavaScript
 * - Added: Fallback payment_date menggunakan created_at jika metadata tidak ada
 *
 * 1.0.1 - 2025-01-17
 * - Fixed: getStatistics() sekarang menggunakan validator pattern (canViewInvoiceStats)
 * - Fixed: getCompanyInvoicePayments() sekarang menggunakan validator pattern (canViewInvoicePayments)
 * - Fixed: Statistik invoice sekarang tampil untuk semua role dengan capability yang sesuai
 * - Removed: Hardcoded manage_options check diganti dengan validator
 *
 * 1.0.0 - 2024-10-08
 * - Initial version
 * - Added core invoice operations
 * - Added AJAX endpoints for invoice management
 * - Added permission validation
 * - Added error handling and caching
 */

namespace WPCustomer\Controllers\Company;

use WPCustomer\Models\Company\CompanyInvoiceModel;
use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\Company\CompanyInvoiceValidator;

class CompanyInvoiceController {
    private $invoice_model;
    private $cache;
    private $validator;

    public function __construct() {
        $this->invoice_model = new CompanyInvoiceModel();
        $this->cache = new CustomerCacheManager();
        $this->validator = new CompanyInvoiceValidator();

        // Register AJAX endpoints
        add_action('wp_ajax_get_company_invoices', [$this, 'getInvoices']);
        add_action('wp_ajax_get_company_invoice_details', [$this, 'getInvoiceDetails']);
        add_action('wp_ajax_create_company_invoice', [$this, 'createInvoice']);
        add_action('wp_ajax_update_company_invoice', [$this, 'updateInvoice']);
        add_action('wp_ajax_delete_company_invoice', [$this, 'deleteInvoice']);
        add_action('wp_ajax_mark_invoice_paid', [$this, 'markInvoicePaid']);
        add_action('wp_ajax_get_unpaid_invoices', [$this, 'getUnpaidInvoices']);

        // Register DataTable and panel handlers
        add_action('wp_ajax_handle_company_invoice_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_company_invoice_stats', [$this, 'getStatistics']);
        add_action('wp_ajax_get_company_invoice_payments', [$this, 'getCompanyInvoicePayments']);
        add_action('wp_ajax_handle_invoice_payment', [$this, 'handle_invoice_payment']);
    }

    /**
     * Validate common request parameters and permissions
     *
     * @param string $nonce_action The nonce action to verify
     * @param string $permission The permission to check (optional)
     * @return int|WP_Error Customer ID if valid, WP_Error otherwise
     */
    private function validateRequest($nonce_action = 'wp_customer_nonce', $permission = '') {
        // Verify nonce
        check_ajax_referer($nonce_action, 'nonce');

        // Get customer ID from request
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if (!$customer_id) {
            return new \WP_Error('invalid_params', __('ID Customer tidak valid', 'wp-customer'));
        }

        // Check permissions if specified
        if (!empty($permission) && !current_user_can($permission)) {
            if (!$this->userCanAccessCustomer($customer_id)) {
                return new \WP_Error('access_denied', __('Anda tidak memiliki izin untuk mengakses data ini', 'wp-customer'));
            }
        }

        return $customer_id;
    }

    /**
     * Check if current user can access customer data
     *
     * @param int $customer_id The customer ID to check
     * @return bool True if user can access, false otherwise
     */
    private function userCanAccessCustomer($customer_id) {
        // Admin can access any customer
        if (current_user_can('manage_options')) {
            return true;
        }

        // Get current user ID
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            return false;
        }

        // Check if user is owner of the customer
        $customer = $this->invoice_model->getCustomerData($customer_id);
        if ($customer && $customer->user_id == $current_user_id) {
            return true;
        }

        return false;
    }

    /**
     * Get invoices for a customer
     */
    public function getInvoices() {
        try {
            $result = $this->validateRequest();
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }

            $customer_id = $result;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                $limit = 50;
            }

            // Get invoices
            $args = [
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset
            ];
            $invoices = $this->invoice_model->findByCustomer($customer_id, $args);

            // Format response
            $formatted_invoices = [];
            foreach ($invoices as $invoice) {
                $formatted_invoices[] = $this->formatInvoiceData($invoice);
            }

            wp_send_json_success([
                'invoices' => $formatted_invoices,
                'total' => count($formatted_invoices),
                'has_more' => count($invoices) === $limit
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get invoice details
     */
    public function getInvoiceDetails() {
        try {
            // Verify nonce
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // Get invoice_id from POST (either 'id' or 'invoice_id')
            $invoice_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$invoice_id) {
                $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
            }

            if (!$invoice_id) {
                throw new \Exception(__('ID Invoice tidak valid', 'wp-customer'));
            }

            // Validate access to view this specific invoice
            $access_check = $this->validator->canViewInvoice($invoice_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get invoice with related data
            $invoice = $this->invoice_model->find($invoice_id);
            if (!$invoice) {
                throw new \Exception(__('Invoice tidak ditemukan', 'wp-customer'));
            }

            wp_send_json_success($this->formatInvoiceData($invoice));

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create new invoice
     */
    public function createInvoice() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $due_date = isset($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : '';
            $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

            // Validate required fields
            if (!$branch_id || $amount <= 0 || empty($due_date)) {
                throw new \Exception(__('Data invoice tidak lengkap', 'wp-customer'));
            }

            // Validate access to create invoice for this branch
            $access_check = $this->validator->canCreateInvoice($branch_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Prepare invoice data
            $invoice_data = [
                'customer_id' => $customer_id,
                'branch_id' => $branch_id,
                'amount' => $amount,
                'due_date' => $due_date,
                'description' => $description
            ];

            // Create invoice
            $invoice_id = $this->invoice_model->create($invoice_data);
            if (!$invoice_id) {
                throw new \Exception(__('Gagal membuat invoice', 'wp-customer'));
            }

            // Get created invoice
            $invoice = $this->invoice_model->find($invoice_id);

            wp_send_json_success([
                'message' => __('Invoice berhasil dibuat', 'wp-customer'),
                'invoice' => $this->formatInvoiceData($invoice)
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update invoice
     */
    public function updateInvoice() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
            $due_date = isset($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : null;
            $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : null;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;

            if (!$invoice_id) {
                throw new \Exception(__('ID Invoice tidak valid', 'wp-customer'));
            }

            // Validate access to edit this invoice
            $access_check = $this->validator->canEditInvoice($invoice_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get existing invoice
            $invoice = $this->invoice_model->find($invoice_id);
            if (!$invoice) {
                throw new \Exception(__('Invoice tidak ditemukan', 'wp-customer'));
            }

            // Prepare update data
            $update_data = [];
            if ($amount !== null && $amount > 0) {
                $update_data['amount'] = $amount;
            }
            if ($due_date !== null) {
                if (strtotime($due_date) <= time()) {
                    throw new \Exception(__('Tanggal jatuh tempo harus di masa depan', 'wp-customer'));
                }
                $update_data['due_date'] = $due_date;
            }
            if ($description !== null) {
                $update_data['description'] = $description;
            }
            if ($status !== null && in_array($status, ['pending', 'paid', 'overdue', 'cancelled'])) {
                $update_data['status'] = $status;
            }

            if (empty($update_data)) {
                throw new \Exception(__('Tidak ada data yang diupdate', 'wp-customer'));
            }

            // Update invoice
            $result = $this->invoice_model->update($invoice_id, $update_data);
            if (!$result) {
                throw new \Exception(__('Gagal mengupdate invoice', 'wp-customer'));
            }

            // Get updated invoice
            $updated_invoice = $this->invoice_model->find($invoice_id);

            wp_send_json_success([
                'message' => __('Invoice berhasil diupdate', 'wp-customer'),
                'invoice' => $this->formatInvoiceData($updated_invoice)
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

            if (!$invoice_id) {
                throw new \Exception(__('ID Invoice tidak valid', 'wp-customer'));
            }

            // Validate access to delete this invoice
            $access_check = $this->validator->canDeleteInvoice($invoice_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get invoice before deletion
            $invoice = $this->invoice_model->find($invoice_id);
            if (!$invoice) {
                throw new \Exception(__('Invoice tidak ditemukan', 'wp-customer'));
            }

            // Delete invoice
            $result = $this->invoice_model->delete($invoice_id);
            if (!$result) {
                throw new \Exception(__('Gagal menghapus invoice', 'wp-customer'));
            }

            wp_send_json_success([
                'message' => __('Invoice berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoicePaid() {
        try {
            // Only admin/staff can mark invoices as paid
            if (!current_user_can('manage_options') && !current_user_can('manage_customer_payments')) {
                throw new \Exception(__('Anda tidak memiliki izin untuk mengelola pembayaran', 'wp-customer'));
            }

            check_ajax_referer('wp_customer_nonce', 'nonce');

            $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
            $payment_date = isset($_POST['payment_date']) ? sanitize_text_field($_POST['payment_date']) : '';

            if (!$invoice_id) {
                throw new \Exception(__('ID Invoice tidak valid', 'wp-customer'));
            }

            // Get invoice
            $invoice = $this->invoice_model->find($invoice_id);
            if (!$invoice) {
                throw new \Exception(__('Invoice tidak ditemukan', 'wp-customer'));
            }

            if ($invoice->status === 'paid') {
                throw new \Exception(__('Invoice sudah lunas', 'wp-customer'));
            }

            // Mark as paid
            $result = $this->invoice_model->markAsPaid($invoice_id, $payment_date);
            if (!$result) {
                throw new \Exception(__('Gagal mengupdate status pembayaran', 'wp-customer'));
            }

            // Get updated invoice
            $updated_invoice = $this->invoice_model->find($invoice_id);

            wp_send_json_success([
                'message' => __('Invoice berhasil ditandai sebagai lunas', 'wp-customer'),
                'invoice' => $this->formatInvoiceData($updated_invoice)
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get unpaid invoices for a customer
     */
    public function getUnpaidInvoices() {
        try {
            $result = $this->validateRequest();
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }

            $customer_id = $result;

            // Get unpaid invoices
            $invoices = $this->invoice_model->getUnpaidInvoices($customer_id);

            // Format response
            $formatted_invoices = [];
            $total_amount = 0;
            foreach ($invoices as $invoice) {
                $formatted_invoices[] = $this->formatInvoiceData($invoice);
                $total_amount += $invoice->amount;
            }

            wp_send_json_success([
                'invoices' => $formatted_invoices,
                'total_count' => count($formatted_invoices),
                'total_amount' => $total_amount
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format invoice data for API response
     *
     * @param object $invoice Raw invoice data
     * @return array Formatted invoice data
     */
    private function formatInvoiceData($invoice) {
        global $wpdb;

        // Get branch, customer, and level data in single query (more efficient)
        $branch_data = null;
        $level_name = '-';

        if ($invoice->branch_id) {
            $branch_data = $wpdb->get_row($wpdb->prepare("
                SELECT
                    b.name as branch_name,
                    c.name as customer_name
                FROM {$wpdb->prefix}app_customer_branches b
                LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                WHERE b.id = %d
            ", $invoice->branch_id));
        }

        // Get from_level name
        $from_level_name = '-';
        if (!empty($invoice->from_level_id)) {
            $from_level_data = $wpdb->get_row($wpdb->prepare("
                SELECT name FROM {$wpdb->prefix}app_customer_membership_levels
                WHERE id = %d
            ", $invoice->from_level_id));

            if ($from_level_data) {
                $from_level_name = $from_level_data->name;
            }
        }

        // Get to_level name
        if (!empty($invoice->level_id)) {
            $level_data = $wpdb->get_row($wpdb->prepare("
                SELECT name FROM {$wpdb->prefix}app_customer_membership_levels
                WHERE id = %d
            ", $invoice->level_id));

            if ($level_data) {
                $level_name = $level_data->name;
            }
        }

        // Set branch and customer names
        $branch_name = $branch_data && $branch_data->branch_name ? $branch_data->branch_name : '-';
        $customer_name = $branch_data && $branch_data->customer_name ? $branch_data->customer_name : '-';

        // Get created_by user name
        $created_by_name = '-';
        if (!empty($invoice->created_by)) {
            $user = get_userdata($invoice->created_by);
            if ($user) {
                $created_by_name = $user->display_name ?: $user->user_login;
            }
        }

        // Calculate is_overdue directly without calling isOverdue() to avoid duplicate find()
        $is_overdue = false;
        if (($invoice->status ?? 'pending') === 'pending' && !empty($invoice->due_date)) {
            $is_overdue = strtotime($invoice->due_date) < time();
        }

        // Check if current user can pay this invoice (for button visibility)
        $can_pay = false;
        $payment_check = $this->validator->canPayInvoice($invoice->id);
        if (!is_wp_error($payment_check)) {
            $can_pay = true;
        }

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number ?? '',
            'customer_id' => $invoice->customer_id,
            'customer_name' => $customer_name,
            'branch_id' => $invoice->branch_id,
            'branch_name' => $branch_name,
            'from_level_id' => $invoice->from_level_id ?? null,
            'from_level_name' => $from_level_name,
            'level_id' => $invoice->level_id ?? null,
            'level_name' => $level_name,
            'is_upgrade' => ($invoice->from_level_id && $invoice->level_id && $invoice->from_level_id != $invoice->level_id),
            'period_months' => $invoice->period_months ?? 1,
            'amount' => floatval($invoice->amount ?? 0),
            'status' => $invoice->status ?? 'pending',
            'status_label' => $this->invoice_model->getStatusLabel($invoice->status ?? 'pending'),
            'due_date' => $invoice->due_date ?? '',
            'paid_date' => $invoice->paid_date ?? null,
            'description' => $invoice->description ?? '',
            'created_by' => $invoice->created_by ?? 0,
            'created_by_name' => $created_by_name,
            'created_at' => $invoice->created_at ?? '',
            'updated_at' => $invoice->updated_at ?? '',
            'is_overdue' => $is_overdue,
            'can_pay' => $can_pay  // Add payment permission flag for JavaScript
        ];
    }

    /**
     * Render main page template
     */
    public function render_page() {
        // Validate user access to view invoice list
        $access_check = $this->validator->canViewInvoiceList();
        if (is_wp_error($access_check)) {
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/company-invoice-no-access.php';
            return;
        }

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/company-invoice-dashboard.php';
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            // Validate access using validator
            $access_check = $this->validator->canViewInvoiceList();
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get DataTable parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            $orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'desc';

            // Map column index to field name
            $columns = ['invoice_number', 'company_name', 'amount', 'status', 'created_at'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

            // Get data from model
            $result = $this->invoice_model->getDataTableData([
                'start' => $start,
                'length' => $length,
                'search' => $search,
                'order_column' => $orderColumn,
                'order_dir' => $orderDir
            ]);

            wp_send_json([
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get invoice statistics for dashboard
     */
    public function getStatistics() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            // Validate access using validator
            $access_check = $this->validator->canViewInvoiceStats();
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            $stats = $this->invoice_model->getStatistics();

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get company invoice payments for payment info tab
     */
    public function getCompanyInvoicePayments() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            $invoice_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if (!$invoice_id) {
                throw new \Exception('Invalid invoice ID');
            }

            // Validate access using validator with invoice_id
            $access_check = $this->validator->canViewInvoicePayments($invoice_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get payments
            $payments = $this->invoice_model->getInvoicePayments($invoice_id);

            // DEBUG: Log raw payment count
            error_log("[DEBUG Review-03] Invoice ID: {$invoice_id}, Raw payments count: " . count($payments));

            // Format payments - extract metadata fields for JavaScript
            $formatted_payments = [];
            foreach ($payments as $payment) {
                // DEBUG: Log raw payment object
                error_log("[DEBUG Review-03] Raw payment ID {$payment->id}: " . json_encode($payment));

                $metadata = json_decode($payment->metadata, true);

                // DEBUG: Log parsed metadata
                error_log("[DEBUG Review-03] Parsed metadata for payment {$payment->id}: " . json_encode($metadata));

                $formatted_payment = [
                    'id' => $payment->id,
                    'payment_id' => $payment->payment_id,
                    'amount' => floatval($payment->amount),
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'payment_date' => $metadata['payment_date'] ?? $payment->created_at, // Use metadata payment_date or created_at as fallback
                    'notes' => $payment->description ?? null,
                    'created_at' => $payment->created_at
                ];

                // DEBUG: Log formatted payment
                error_log("[DEBUG Review-03] Formatted payment {$payment->id}: " . json_encode($formatted_payment));

                $formatted_payments[] = $formatted_payment;
            }

            // DEBUG: Log final response
            error_log("[DEBUG Review-03] Final formatted_payments count: " . count($formatted_payments));
            error_log("[DEBUG Review-03] Final response: " . json_encode(['payments' => $formatted_payments]));

            wp_send_json_success([
                'payments' => $formatted_payments
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle invoice payment
     * Uses role-based access control:
     * - administrator: can pay all invoices
     * - customer_admin: can pay all invoices under their customer
     * - customer_branch_admin: can pay only their branch invoices
     * - customer_employee: cannot pay invoices
     */
    public function handle_invoice_payment() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
            $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

            if (!$invoice_id || !$payment_method) {
                throw new \Exception(__('Invalid parameters', 'wp-customer'));
            }

            // Validate access using validator - implements role-based access
            $access_check = $this->validator->canPayInvoice($invoice_id);
            if (is_wp_error($access_check)) {
                throw new \Exception($access_check->get_error_message());
            }

            // Get invoice (already validated in canPayInvoice)
            $invoice = $this->invoice_model->find($invoice_id);

            // Validate payment method
            $valid_methods = ['transfer_bank', 'virtual_account', 'kartu_kredit', 'e_wallet'];
            if (!in_array($payment_method, $valid_methods)) {
                throw new \Exception(__('Metode pembayaran tidak valid', 'wp-customer'));
            }

            // Mark invoice as paid
            $paid_date = current_time('mysql');
            $result = $this->invoice_model->markAsPaid($invoice_id, $paid_date);

            if (!$result) {
                throw new \Exception(__('Gagal memproses pembayaran', 'wp-customer'));
            }

            // Create payment record
            global $wpdb;
            $payment_table = $wpdb->prefix . 'app_customer_payments';

            $payment_id = 'PAY-' . date('Ymd') . '-' . sprintf('%05d', rand(10000, 99999));
            $payment_data = [
                'payment_id' => $payment_id,
                'company_id' => $invoice->customer_id,
                'amount' => $invoice->amount,
                'payment_method' => $payment_method,
                'description' => sprintf(__('Payment for invoice %s', 'wp-customer'), $invoice->invoice_number),
                'metadata' => json_encode([
                    'invoice_id' => $invoice_id,
                    'invoice_number' => $invoice->invoice_number,
                    'payment_method' => $payment_method,
                    'payment_date' => $paid_date
                ]),
                'status' => 'completed',
                'created_at' => $paid_date,
                'updated_at' => $paid_date
            ];

            $wpdb->insert($payment_table, $payment_data);

            // Get updated invoice
            $updated_invoice = $this->invoice_model->find($invoice_id);

            // Clear cache - use public clearAllCaches() method
            $this->cache->clearAllCaches();

            wp_send_json_success([
                'message' => __('Payment processed successfully', 'wp-customer'),
                'invoice' => $this->formatInvoiceData($updated_invoice)
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

}
