<?php
/**
 * Company Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyDashboardController.php
 *
 * Description: Dashboard controller untuk Company (Branch) management.
 *              Uses wp-datatable DualPanel framework.
 *              Pattern sama dengan CustomerDashboardController.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195: Company DualPanel Refactoring)
 * - Initial implementation following CustomerDashboardController pattern
 * - Uses wp-datatable DualPanel layout system
 * - Hook-based architecture: wpdt_*
 * - Nonce: wpdt_nonce
 * - Tabs: Info, Branches (if company has sub-branches), Staff
 */

namespace WPCustomer\Controllers\Company;

use WPDataTable\Templates\DualPanel\DashboardTemplate;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Company\CompanyDataTableModel;
use WPCustomer\Models\Employee\EmployeeDataTableModel;
use WPCustomer\Validators\Branch\BranchValidator;

defined('ABSPATH') || exit;

class CompanyDashboardController {

    /**
     * @var BranchModel
     */
    private $model;

    /**
     * @var CompanyDataTableModel
     */
    private $datatable_model;

    /**
     * @var BranchValidator
     */
    private $validator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new BranchModel();
        $this->datatable_model = new CompanyDataTableModel();
        $this->validator = new BranchValidator();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Signal wp-datatable to load dual panel assets
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel'], 10, 1);

        // Register tabs
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Register content hooks
        add_action('wpdt_left_panel_content', [$this, 'render_datatable'], 10, 1);
        add_action('wpdt_statistics_content', [$this, 'render_statistics'], 10, 1);

        // AJAX handlers - Dashboard
        add_action('wp_ajax_get_company_datatable', [$this, 'handle_datatable']);
        add_action('wp_ajax_get_company_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_company_stats', [$this, 'handle_get_stats']);

        // AJAX handlers - Tab lazy loading
        add_action('wp_ajax_load_company_info_tab', [$this, 'handle_load_info_tab']);
        add_action('wp_ajax_load_company_staff_tab', [$this, 'handle_load_staff_tab']);

        // AJAX handlers - DataTables in tabs
        add_action('wp_ajax_get_company_employees_datatable', [$this, 'handle_employees_datatable']);
    }

    /**
     * Render dashboard page
     * Called from MenuManager
     */
    public function render(): void {
        // Check permission
        if (!current_user_can('view_customer_branch_list')) {
            wp_die(__('You do not have permission to access this page.', 'wp-customer'));
        }

        // Render wp-datatable dual panel dashboard
        DashboardTemplate::render([
            'entity' => 'company',
            'title' => __('Companies', 'wp-customer'),
            'description' => __('Manage your companies', 'wp-customer'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_company_details',
        ]);
    }

    // ========================================
    // DUAL PANEL SIGNAL
    // ========================================

    /**
     * Signal wp-datatable to use dual panel layout
     */
    public function signal_dual_panel($use): bool {
        error_log('[CompanyDashboard] signal_dual_panel called, page=' . ($_GET['page'] ?? 'none'));
        if (isset($_GET['page']) && $_GET['page'] === 'perusahaan') {
            error_log('[CompanyDashboard] Returning true for dual panel');
            return true;
        }
        error_log('[CompanyDashboard] Returning false for dual panel');
        return $use;
    }

    // ========================================
    // TAB REGISTRATION
    // ========================================

    /**
     * Register tabs for company dashboard
     */
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'company') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Company Information', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/info.php',
                'priority' => 10
            ],
            'staff' => [
                'title' => __('Staff', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/staff.php',
                'priority' => 20
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
        if ($config['entity'] !== 'company') {
            return;
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/company/datatable/datatable.php';

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
        if ($config['entity'] !== 'company') {
            return;
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/company/statistics/statistics.php';

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

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading companies', 'wp-customer')]);
        }
    }

    /**
     * Handle get company details for detail panel
     */
    public function handle_get_details(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $company_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'wp-customer')]);
            return;
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'wp-customer')]);
                return;
            }

            // Render tabs content
            $tabs = $this->render_tabs_content($company);

            wp_send_json_success([
                'title' => esc_html($company->name),
                'tabs' => $tabs
            ]);

        } catch (\Exception $e) {
            error_log('[CompanyDashboard] Error in handle_get_details: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading company details', 'wp-customer')]);
        }
    }

    /**
     * Render tabs content for detail panel
     */
    private function render_tabs_content($company): array {
        $tabs_content = [];
        $registered_tabs = $this->register_tabs([], 'company');

        foreach ($registered_tabs as $tab_id => $tab) {
            if (!isset($tab['template']) || !file_exists($tab['template'])) {
                continue;
            }

            ob_start();
            $data = $company; // Make $data available to template
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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            global $wpdb;
            $table = $wpdb->prefix . 'app_customer_branches';

            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
            $inactive = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive'");

            wp_send_json_success([
                'total' => (int) $total,
                'active' => (int) $active,
                'inactive' => (int) $inactive
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading statistics', 'wp-customer')]);
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

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'wp-customer')]);
            return;
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/partials/info-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading info tab', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy load staff tab content
     */
    public function handle_load_staff_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Company ID required', 'wp-customer')]);
            return;
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                wp_send_json_error(['message' => __('Company not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/partials/staff-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading staff tab', 'wp-customer')]);
        }
    }

    // ========================================
    // AJAX HANDLERS - DATATABLES IN TABS
    // ========================================

    /**
     * Handle employees DataTable AJAX (for staff tab)
     */
    public function handle_employees_datatable(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_branch_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $model = new EmployeeDataTableModel();

            // Filter by branch_id (company_id in this context)
            $_POST['branch_id'] = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
        }
    }
}
