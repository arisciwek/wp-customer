<?php
/**
 * Company Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyDashboardController.php
 *
 * Description: Dashboard controller untuk Company (Branch) management.
 *              Uses wp-datatable DualPanel framework.
 *              Pattern sama dengan CustomerDashboardController.
 *
 * Changelog:
 * 1.1.0 - 2025-12-25
 * - Added: Modal CRUD handlers (get_company_form, save_company, delete_company)
 * - Added: Edit company functionality via modal
 * - Added: Delete company functionality with confirmation
 * - Form: edit-company-form.php with full branch fields
 * - JS: company-modal.js for handling edit/delete operations
 * - Integration: Wilayah cascade for province/regency selection
 *
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
use WPCustomer\Models\Company\CompanyModel;
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
     * @var CompanyModel
     */
    private $company_model;

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
        $this->company_model = new CompanyModel();
        $this->datatable_model = new CompanyDataTableModel();
        $this->validator = new BranchValidator();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

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

        // AJAX handlers - Modal CRUD
        add_action('wp_ajax_get_company_form', [$this, 'handle_get_company_form']);
        add_action('wp_ajax_save_company', [$this, 'handle_save_company']);
        add_action('wp_ajax_delete_company', [$this, 'handle_delete_company']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook): void {
        // Only load on company dashboard page
        if ($hook !== 'toplevel_page_perusahaan') {
            return;
        }

        // Enqueue company info tab styles
        wp_enqueue_style(
            'wp-customer-company-info',
            WP_CUSTOMER_URL . 'assets/css/admin/company-info.css',
            [],
            WP_CUSTOMER_VERSION
        );
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
            // Use CompanyModel to get branch with all related data (province, city, agency, division, inspector)
            $company = $this->company_model->getBranchWithLatestMembership($company_id);

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
            $branch_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;

            if (!$branch_id) {
                wp_send_json_error(['message' => __('Branch ID required', 'wp-customer')]);
                return;
            }

            // Get customer_id from branch record
            global $wpdb;
            $branch = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_id FROM {$wpdb->prefix}app_customer_branches WHERE id = %d",
                $branch_id
            ));

            if (!$branch) {
                wp_send_json_error(['message' => __('Branch not found', 'wp-customer')]);
                return;
            }

            $model = new EmployeeDataTableModel();

            // Set both customer_id and branch_id for filtering
            $_POST['customer_id'] = $branch->customer_id;
            $_POST['branch_id'] = $branch_id;

            error_log("[CompanyDashboard] Employees DataTable request - branch_id: {$branch_id}, customer_id: {$branch->customer_id}");

            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            error_log('[CompanyDashboard] Error in handle_employees_datatable: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
        }
    }

    // ========================================
    // AJAX HANDLERS - MODAL CRUD
    // ========================================

    /**
     * Handle get company form (edit only - no create for company)
     */
    public function handle_get_company_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            echo '<p class="error">' . __('Security check failed', 'wp-customer') . '</p>';
            wp_die();
        }

        $company_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        // Check permissions
        if (!current_user_can('manage_options') &&
            !current_user_can('edit_all_customer_branches') &&
            !current_user_can('edit_own_customer_branch')) {
            echo '<p class="error">' . __('Permission denied', 'wp-customer') . '</p>';
            wp_die();
        }

        if (!$company_id) {
            echo '<p class="error">' . __('Invalid company ID', 'wp-customer') . '</p>';
            wp_die();
        }

        try {
            $company = $this->model->find($company_id);

            if (!$company) {
                echo '<p class="error">' . __('Company not found', 'wp-customer') . '</p>';
                wp_die();
            }

            include WP_CUSTOMER_PATH . 'src/Views/admin/company/forms/edit-company-form.php';

        } catch (\Exception $e) {
            echo '<p class="error">' . esc_html($e->getMessage()) . '</p>';
        }

        wp_die();
    }

    /**
     * Handle save company (update only)
     */
    public function handle_save_company(): void {
        @ini_set('display_errors', '0');
        ob_start();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        // Check permissions
        if (!current_user_can('manage_options') &&
            !current_user_can('edit_all_customer_branches') &&
            !current_user_can('edit_own_customer_branch')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            wp_die();
        }

        $company_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$company_id) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Invalid company ID', 'wp-customer')]);
            wp_die();
        }

        // Prepare data
        $data = [
            'customer_id' => isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0,
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? 'cabang'),
            'nitku' => sanitize_text_field($_POST['nitku'] ?? ''),
            'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
            'latitude' => isset($_POST['latitude']) ? floatval($_POST['latitude']) : null,
            'longitude' => isset($_POST['longitude']) ? floatval($_POST['longitude']) : null,
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'province_id' => !empty($_POST['province_id']) ? (int) $_POST['province_id'] : null,
            'regency_id' => !empty($_POST['regency_id']) ? (int) $_POST['regency_id'] : null,
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        ];

        // Validate
        $validation_errors = $this->validator->validateForm($data, $company_id);
        if (!empty($validation_errors)) {
            ob_end_clean();
            wp_send_json_error(['message' => implode(' ', $validation_errors)]);
            wp_die();
        }

        try {
            // Update company (branch)
            $result = $this->model->update($company_id, $data);

            if ($result) {
                $company = $this->model->find($company_id);

                ob_end_clean();
                wp_send_json_success([
                    'message' => __('Company updated successfully', 'wp-customer'),
                    'company' => $company
                ]);
                wp_die();
            } else {
                ob_end_clean();
                wp_send_json_error(['message' => __('Failed to update company', 'wp-customer')]);
                wp_die();
            }

        } catch (\Exception $e) {
            ob_end_clean();
            wp_send_json_error(['message' => $e->getMessage()]);
            wp_die();
        }
    }

    /**
     * Handle delete company
     */
    public function handle_delete_company(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        if (!current_user_can('manage_options') &&
            !current_user_can('delete_all_customer_branches') &&
            !current_user_can('delete_own_customer_branch')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            wp_die();
        }

        $company_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$company_id) {
            wp_send_json_error(['message' => __('Invalid company ID', 'wp-customer')]);
            wp_die();
        }

        try {
            // Use model delete() which triggers hooks for cascade operations
            $result = $this->model->delete($company_id);

            if ($result) {
                wp_send_json_success(['message' => __('Company deleted successfully', 'wp-customer')]);
            } else {
                wp_send_json_error(['message' => __('Company not found or failed to delete', 'wp-customer')]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
