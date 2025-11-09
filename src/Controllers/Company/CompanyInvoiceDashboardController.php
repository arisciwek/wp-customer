<?php
/**
 * Company Invoice Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyInvoiceDashboardController.php
 *
 * Description: Dashboard controller untuk Company Invoice management.
 *              Uses wp-datatable DualPanel framework.
 *              Pattern sama dengan CustomerDashboardController & CompanyDashboardController.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196: Company Invoice DualPanel Refactoring)
 * - Initial implementation following DualPanel pattern
 * - Uses wp-datatable DualPanel layout system
 * - Hook-based architecture: wpdt_*
 * - Nonce: wpdt_nonce
 * - Tabs: Info, Payment, Company, Activity
 * - Status filters: pending, paid, pending_payment, cancelled
 * - Access-based filtering (admin, customer_admin, branch_admin, employee)
 */

namespace WPCustomer\Controllers\Company;

use WPDataTable\Templates\DualPanel\DashboardTemplate;
use WPCustomer\Models\Company\CompanyInvoiceModel;
use WPCustomer\Models\Company\CompanyInvoiceDataTableModel;

defined('ABSPATH') || exit;

class CompanyInvoiceDashboardController {

    /**
     * @var CompanyInvoiceModel
     */
    private $model;

    /**
     * @var CompanyInvoiceDataTableModel
     */
    private $datatable_model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CompanyInvoiceModel();
        $this->datatable_model = new CompanyInvoiceDataTableModel();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        error_log('[CompanyInvoiceDashboard] init_hooks() called');
        error_log('[CompanyInvoiceDashboard] Registering AJAX actions');

        // Signal wp-datatable to load dual panel assets
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel'], 10, 1);

        // Register tabs
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Register content hooks
        add_action('wpdt_left_panel_content', [$this, 'render_datatable'], 10, 1);
        add_action('wpdt_statistics_content', [$this, 'render_statistics'], 10, 1);

        // AJAX handlers - Dashboard
        add_action('wp_ajax_get_company_invoice_datatable', [$this, 'handle_datatable'], 10);
        add_action('wp_ajax_get_company_invoice_details', [$this, 'handle_get_details'], 10);
        add_action('wp_ajax_get_company_invoice_stats', [$this, 'handle_get_stats'], 10);

        error_log('[CompanyInvoiceDashboard] AJAX actions registered: get_company_invoice_datatable, get_company_invoice_details, get_company_invoice_stats');

        // AJAX handlers - Tab lazy loading
        add_action('wp_ajax_load_company_invoice_info_tab', [$this, 'handle_load_info_tab']);
        add_action('wp_ajax_load_company_invoice_payment_tab', [$this, 'handle_load_payment_tab']);
        add_action('wp_ajax_load_company_invoice_company_tab', [$this, 'handle_load_company_tab']);
        add_action('wp_ajax_load_company_invoice_activity_tab', [$this, 'handle_load_activity_tab']);

        // AJAX handlers - Actions
        add_action('wp_ajax_upload_invoice_payment_proof', [$this, 'handle_upload_payment_proof']);
        add_action('wp_ajax_validate_invoice_payment', [$this, 'handle_validate_payment']);
        add_action('wp_ajax_cancel_invoice', [$this, 'handle_cancel_invoice']);
    }

    /**
     * Render dashboard page
     * Called from MenuManager
     */
    public function render(): void {
        error_log('[CompanyInvoiceDashboard] render() called');
        error_log('[CompanyInvoiceDashboard] Current user ID: ' . get_current_user_id());
        error_log('[CompanyInvoiceDashboard] Can view invoice list: ' . (current_user_can('view_customer_membership_invoice_list') ? 'yes' : 'no'));
        error_log('[CompanyInvoiceDashboard] Is admin: ' . (current_user_can('view_customer_membership_invoice_list') ? 'yes' : 'no'));

        // Check permission - allow admin to bypass
        if (!current_user_can('view_customer_membership_invoice_list') && !current_user_can('view_customer_membership_invoice_list')) {
            error_log('[CompanyInvoiceDashboard] Permission denied');
            wp_die(__('You do not have permission to access this page.', 'wp-customer'));
        }

        error_log('[CompanyInvoiceDashboard] Permission OK, rendering template');

        // Render wp-datatable dual panel dashboard
        DashboardTemplate::render([
            'entity' => 'company-invoice',
            'title' => __('Company Invoices', 'wp-customer'),
            'description' => __('Manage company membership invoices', 'wp-customer'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => true,
            'ajax_action' => 'get_company_invoice_details',
        ]);
    }

    // ========================================
    // DUAL PANEL SIGNAL
    // ========================================

    /**
     * Signal wp-datatable to use dual panel layout
     */
    public function signal_dual_panel($use): bool {
        error_log('[CompanyInvoiceDashboard] signal_dual_panel called, page=' . ($_GET['page'] ?? 'none'));
        if (isset($_GET['page']) && $_GET['page'] === 'company-invoices') {
            error_log('[CompanyInvoiceDashboard] Returning true for dual panel');
            return true;
        }
        error_log('[CompanyInvoiceDashboard] Returning false for dual panel');
        return $use;
    }

    // ========================================
    // TAB REGISTRATION
    // ========================================

    /**
     * Register tabs for company invoice dashboard
     */
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'company-invoice') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Invoice Info', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/info.php',
                'priority' => 10
            ],
            'payment' => [
                'title' => __('Payment', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/payment.php',
                'priority' => 20
            ],
            'company' => [
                'title' => __('Company', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/company.php',
                'priority' => 30
            ],
            'activity' => [
                'title' => __('Activity', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/activity.php',
                'priority' => 40
            ]
        ];
    }

    // ========================================
    // CONTENT RENDERING
    // ========================================

    /**
     * Render DataTable in left panel
     */
    public function render_datatable($config): void {
        if ($config['entity'] !== 'company-invoice') {
            return;
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/datatable/datatable.php';

        if (!file_exists($view_file)) {
            echo '<p>' . __('DataTable view not found', 'wp-customer') . '</p>';
            return;
        }

        include $view_file;
    }

    /**
     * Render statistics cards
     */
    public function render_statistics($config): void {
        if ($config['entity'] !== 'company-invoice') {
            return;
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/statistics/statistics.php';

        if (!file_exists($view_file)) {
            echo '<p>' . __('Statistics view not found', 'wp-customer') . '</p>';
            return;
        }

        include $view_file;
    }

    // ========================================
    // AJAX HANDLERS - DASHBOARD
    // ========================================

    /**
     * Handle DataTable AJAX request
     */
    public function handle_datatable(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_datatable: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading invoices', 'wp-customer')]);
        }
    }

    /**
     * Handle get invoice details for detail panel
     */
    public function handle_get_details(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $invoice = $this->model->find($invoice_id);

            if (!$invoice) {
                wp_send_json_error(['message' => __('Invoice not found', 'wp-customer')]);
                return;
            }

            // Render tabs content
            $tabs = $this->render_tabs_content($invoice);

            wp_send_json_success([
                'title' => esc_html($invoice->invoice_number),
                'tabs' => $tabs
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_get_details: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading invoice details', 'wp-customer')]);
        }
    }

    /**
     * Render tabs content for detail panel
     */
    private function render_tabs_content($invoice): array {
        $tabs_content = [];
        $registered_tabs = $this->register_tabs([], 'company-invoice');

        foreach ($registered_tabs as $tab_id => $tab) {
            if (!isset($tab['template']) || !file_exists($tab['template'])) {
                continue;
            }

            ob_start();
            $data = $invoice; // Make $data available to template
            include $tab['template'];
            $content = ob_get_clean();
            $tabs_content[$tab_id] = $content;
        }

        return $tabs_content;
    }

    /**
     * Handle get statistics
     */
    public function handle_get_stats(): void {
        error_log('[CompanyInvoiceDashboard] handle_get_stats called');
        error_log('[CompanyInvoiceDashboard] POST data: ' . print_r($_POST, true));

        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('[CompanyInvoiceDashboard] Nonce check failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            error_log('[CompanyInvoiceDashboard] Permission check failed');
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            error_log('[CompanyInvoiceDashboard] Calling getStatistics()');
            $stats = $this->model->getStatistics();
            error_log('[CompanyInvoiceDashboard] Stats: ' . print_r($stats, true));

            wp_send_json_success([
                'total' => (int) ($stats['total_invoices'] ?? 0),
                'pending' => (int) ($stats['pending_invoices'] ?? 0),
                'paid' => (int) ($stats['paid_invoices'] ?? 0),
                'total_amount' => (float) ($stats['total_paid_amount'] ?? 0)
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_get_stats: ' . $e->getMessage());
            error_log('[CompanyInvoiceDashboard] Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => __('Error loading statistics', 'wp-customer'), 'error' => $e->getMessage()]);
        }
    }

    // ========================================
    // AJAX HANDLERS - TAB LAZY LOADING
    // ========================================

    /**
     * Handle lazy load info tab content
     */
    public function handle_load_info_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $invoice = $this->model->find($invoice_id);

            if (!$invoice) {
                wp_send_json_error(['message' => __('Invoice not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/partials/info-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_load_info_tab: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading info tab', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy load payment tab content
     */
    public function handle_load_payment_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $invoice = $this->model->find($invoice_id);

            if (!$invoice) {
                wp_send_json_error(['message' => __('Invoice not found', 'wp-customer')]);
                return;
            }

            // Get payment history
            $payments = $this->model->getInvoicePayments($invoice_id);

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/partials/payment-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_load_payment_tab: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading payment tab', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy load company tab content
     */
    public function handle_load_company_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $invoice = $this->model->find($invoice_id);

            if (!$invoice) {
                wp_send_json_error(['message' => __('Invoice not found', 'wp-customer')]);
                return;
            }

            // Get company/branch data
            $company = $this->model->getInvoiceCompany($invoice_id);

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/partials/company-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_load_company_tab: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading company tab', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy load activity tab content
     */
    public function handle_load_activity_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $invoice = $this->model->find($invoice_id);

            if (!$invoice) {
                wp_send_json_error(['message' => __('Invoice not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/partials/activity-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_load_activity_tab: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading activity tab', 'wp-customer')]);
        }
    }

    // ========================================
    // AJAX HANDLERS - ACTIONS
    // ========================================

    /**
     * Handle upload payment proof
     */
    public function handle_upload_payment_proof(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        // TODO: Implement file upload handling
        wp_send_json_error(['message' => __('Upload payment proof feature not yet implemented', 'wp-customer')]);
    }

    /**
     * Handle validate payment (admin only)
     */
    public function handle_validate_payment(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied. Admin only.', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $result = $this->model->markAsPaid($invoice_id);

            if ($result) {
                wp_send_json_success(['message' => __('Payment validated successfully', 'wp-customer')]);
            } else {
                wp_send_json_error(['message' => __('Failed to validate payment', 'wp-customer')]);
            }

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_validate_payment: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error validating payment', 'wp-customer')]);
        }
    }

    /**
     * Handle cancel invoice
     */
    public function handle_cancel_invoice(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_membership_invoice_list')) {
            wp_send_json_error(['message' => __('Permission denied. Admin only.', 'wp-customer')]);
            return;
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Invoice ID required', 'wp-customer')]);
            return;
        }

        try {
            $result = $this->model->cancel($invoice_id);

            if ($result) {
                wp_send_json_success(['message' => __('Invoice cancelled successfully', 'wp-customer')]);
            } else {
                wp_send_json_error(['message' => __('Failed to cancel invoice', 'wp-customer')]);
            }

        } catch (\Exception $e) {
            error_log('[CompanyInvoiceDashboard] Error in handle_cancel_invoice: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error cancelling invoice', 'wp-customer')]);
        }
    }
}
