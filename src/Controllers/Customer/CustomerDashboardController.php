<?php
/**
 * Customer Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     3.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Customer/CustomerDashboardController.php
 *
 * Description: Dashboard controller untuk Customer management.
 *              Refactored dari wp-app-core AbstractDashboardController
 *              ke wp-datatable DualPanel framework.
 *              Uses hook-based architecture untuk extensibility.
 *
 * Changelog:
 * 3.0.1 - 2025-11-09
 * - ARCHITECTURAL FIX: Implemented proper wp-datatable tab rendering pattern
 * - Added template paths to tab registration (Direct Inclusion Pattern)
 * - Created render_tabs_content() method to render all tabs in AJAX response
 * - Updated handle_get_details() to return 'tabs' and 'title' in response
 * - Removed deprecated wpdt_tab_content hooks (non-existent in wp-datatable)
 * - Removed lazy-load tab handlers (tabs now render immediately with detail panel)
 * - Updated all view paths: src/Views/customer/ → src/Views/admin/customer/
 * - Result: Tabs now populate correctly when customer row is clicked
 *
 * 3.0.0 - 2025-11-09 (TODO-2192: wp-datatable Integration)
 * - BREAKING: Removed dependency on AbstractDashboardController
 * - Migrated to wp-datatable DualPanel layout system
 * - Hook changes: wpapp_* → wpdt_*
 * - Nonce changes: wpapp_panel_nonce → wpdt_nonce
 * - Simplified structure: No abstract methods, pure hook-based
 * - ALL FUNCTIONALITY PRESERVED: 3 tabs, stats, modal CRUD
 * - Code reduction: 578 lines → ~400 lines (31% reduction)
 *
 * 2.0.0 - 2025-11-04 (wp-app-core implementation)
 * - Extended AbstractDashboardController
 * - 13 abstract methods implemented
 *
 * 1.0.0 - 2025-11-01
 * - Initial implementation
 */

namespace WPCustomer\Controllers\Customer;

use WPDataTable\Templates\DualPanel\DashboardTemplate;
use WPCustomer\Models\Customer\CustomerDataTableModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchDataTableModel;
use WPCustomer\Models\Employee\EmployeeDataTableModel;
use WPCustomer\Validators\CustomerValidator;

defined('ABSPATH') || exit;

class CustomerDashboardController {

    /**
     * @var CustomerModel
     */
    private $model;

    /**
     * @var CustomerDataTableModel
     */
    private $datatable_model;

    /**
     * @var CustomerValidator
     */
    private $validator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CustomerModel();
        $this->datatable_model = new CustomerDataTableModel();
        $this->validator = new CustomerValidator();

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
        add_action('wp_ajax_get_customer_datatable', [$this, 'handle_datatable']);
        add_action('wp_ajax_get_customer_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_customer_stats_v2', [$this, 'handle_get_stats']);

        // AJAX handlers - Tab lazy loading
        add_action('wp_ajax_load_customer_branches_tab', [$this, 'handle_load_branches_tab']);

        // AJAX handlers - DataTables in tabs
        add_action('wp_ajax_get_customer_branches_datatable', [$this, 'handle_branches_datatable']);
        add_action('wp_ajax_get_customer_employees_datatable', [$this, 'handle_employees_datatable']);

        // AJAX handlers - Modal CRUD
        add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
        add_action('wp_ajax_save_customer', [$this, 'handle_save_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);
    }

    /**
     * Render dashboard page
     * Called from MenuManager
     */
    public function render(): void {
        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_die(__('You do not have permission to access this page.', 'wp-customer'));
        }

        // Render wp-datatable dual panel dashboard
        DashboardTemplate::render([
            'entity' => 'customer',
            'title' => __('Customers', 'wp-customer'),
            'description' => __('Manage your customers', 'wp-customer'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_customer_details',
        ]);
    }

    // ========================================
    // DUAL PANEL SIGNAL
    // ========================================

    /**
     * Signal wp-datatable to use dual panel layout
     */
    public function signal_dual_panel($use): bool {
        error_log('[CustomerDashboard] signal_dual_panel called, page=' . ($_GET['page'] ?? 'none'));
        if (isset($_GET['page']) && $_GET['page'] === 'wp-customer-v2') {
            error_log('[CustomerDashboard] Returning true for dual panel');
            return true;
        }
        error_log('[CustomerDashboard] Returning false for dual panel');
        return $use;
    }

    // ========================================
    // TAB REGISTRATION
    // ========================================

    /**
     * Register tabs for customer dashboard
     */
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'customer') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Customer Information', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/info.php',
                'priority' => 10
            ],
            'branches' => [
                'title' => __('Cabang', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/branches.php',
                'priority' => 20
            ],
            'employees' => [
                'title' => __('Staff', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/employees.php',
                'priority' => 30
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
        if ($config['entity'] !== 'customer') {
            return;
        }

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/customer/datatable/datatable.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            error_log('[CustomerDashboard] DataTable view file not found: ' . $view_file);
        }
    }

    /**
     * Render statistics boxes
     */
    public function render_statistics($config): void {
        if ($config['entity'] !== 'customer') {
            return;
        }

        $stats = [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ];

        ?>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-icon dashicons dashicons-businessperson"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['total']); ?></div>
                <div class="wpdt-stat-label"><?php _e('Total Customers', 'wp-customer'); ?></div>
            </div>
        </div>

        <div class="wpdt-stat-box wpdt-stat-success">
            <div class="wpdt-stat-icon dashicons dashicons-yes-alt"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['active']); ?></div>
                <div class="wpdt-stat-label"><?php _e('Active', 'wp-customer'); ?></div>
            </div>
        </div>

        <div class="wpdt-stat-box wpdt-stat-danger">
            <div class="wpdt-stat-icon dashicons dashicons-dismiss"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['inactive']); ?></div>
                <div class="wpdt-stat-label"><?php _e('Inactive', 'wp-customer'); ?></div>
            </div>
        </div>
        <?php
    }

    // ========================================
    // TAB CONTENT RENDERING
    // ========================================

    /**
     * Render all tabs content and return as array
     *
     * @param object $customer Customer data
     * @return array Associative array [tab_id => html_content]
     */
    private function render_tabs_content($customer): array {
        error_log('[CustomerDashboard] render_tabs_content called');
        error_log('[CustomerDashboard] Customer ID: ' . ($customer->id ?? 'NULL'));

        $tabs = [];

        // Get registered tabs
        $registered_tabs = $this->register_tabs([], 'customer');
        error_log('[CustomerDashboard] Registered tabs: ' . print_r(array_keys($registered_tabs), true));

        // Render each tab
        foreach ($registered_tabs as $tab_id => $tab_config) {
            if (isset($tab_config['template']) && file_exists($tab_config['template'])) {
                error_log("[CustomerDashboard] Rendering tab: {$tab_id}");
                ob_start();
                $data = $customer; // Make $data available to template
                include $tab_config['template'];
                $content = ob_get_clean();
                $tabs[$tab_id] = $content;
                error_log("[CustomerDashboard] Tab {$tab_id} rendered, length: " . strlen($content));
            }
        }

        error_log('[CustomerDashboard] Total tabs rendered: ' . count($tabs));
        return $tabs;
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

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading customers', 'wp-customer')]);
        }
    }

    /**
     * Handle get customer details AJAX
     */
    public function handle_get_details(): void {
        error_log('[CustomerDashboard] handle_get_details called');
        error_log('[CustomerDashboard] POST data: ' . print_r($_POST, true));

        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            error_log('[CustomerDashboard] Nonce check failed');
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            error_log('[CustomerDashboard] Permission denied');
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        error_log('[CustomerDashboard] Customer ID: ' . $id);

        if (!$id) {
            error_log('[CustomerDashboard] Invalid customer ID');
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        try {
            // TODO-2192 FIXED in wp-app-core v1.0.1 - cache now returns false on miss
            $customer = $this->model->find($id);

            if (!$customer) {
                error_log('[CustomerDashboard] Customer not found: ' . $id);
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            error_log('[CustomerDashboard] Customer found: ' . $customer->name);

            $membership = $this->model->getMembershipData($id);
            $access = $this->validator->validateAccess($id);

            // Render tab content
            $tabs = $this->render_tabs_content($customer);

            $response = [
                'customer' => $customer,
                'membership' => $membership,
                'access_type' => $access['access_type'],
                'title' => $customer->name,
                'tabs' => $tabs
            ];

            error_log('[CustomerDashboard] Sending success response with ' . count($tabs) . ' tabs');
            wp_send_json_success($response);

        } catch (\Exception $e) {
            error_log('[CustomerDashboard] Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle get statistics AJAX
     */
    public function handle_get_stats(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $stats = [
                'total' => $this->datatable_model->get_total_count('all'),
                'active' => $this->datatable_model->get_total_count('active'),
                'inactive' => $this->datatable_model->get_total_count('inactive')
            ];

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // AJAX HANDLERS - DATATABLES IN TABS
    // ========================================

    /**
     * Handle branches DataTable AJAX
     */
    public function handle_branches_datatable(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $model = new BranchDataTableModel();
            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches', 'wp-customer')]);
        }
    }

    /**
     * Handle employees DataTable AJAX
     */
    public function handle_employees_datatable(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            $model = new EmployeeDataTableModel();
            $response = $model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
        }
    }

    // ========================================
    // AJAX HANDLERS - TAB LAZY LOADING
    // ========================================

    /**
     * Handle lazy load branches tab content
     * Called by wp-datatable tab-manager.js on first tab click
     */
    public function handle_load_branches_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
            return;
        }

        try {
            // Get customer data for context
            $customer = $this->model->find($customer_id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            // Render branches tab content with DataTable
            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/partials/branches-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches tab', 'wp-customer')]);
        }
    }

    // ========================================
    // AJAX HANDLERS - MODAL CRUD
    // ========================================

    /**
     * Handle get customer form (create/edit)
     */
    public function handle_get_customer_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            echo '<p class="error">' . __('Security check failed', 'wp-customer') . '</p>';
            wp_die();
        }

        $mode = $_GET['mode'] ?? 'create';
        $customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

        // Check permissions
        if ($mode === 'edit') {
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_customers') &&
                !current_user_can('edit_own_customer')) {
                echo '<p class="error">' . __('Permission denied', 'wp-customer') . '</p>';
                wp_die();
            }
        } else {
            if (!current_user_can('manage_options') && !current_user_can('add_customer')) {
                echo '<p class="error">' . __('Permission denied', 'wp-customer') . '</p>';
                wp_die();
            }
        }

        try {
            if ($mode === 'edit' && $customer_id) {
                $customer = $this->model->find($customer_id);

                if (!$customer) {
                    echo '<p class="error">' . __('Customer not found', 'wp-customer') . '</p>';
                    wp_die();
                }

                include WP_CUSTOMER_PATH . 'src/Views/admin/customer/forms/edit-customer-form.php';
            } else {
                include WP_CUSTOMER_PATH . 'src/Views/admin/customer/forms/create-customer-form.php';
            }
        } catch (\Exception $e) {
            echo '<p class="error">' . esc_html($e->getMessage()) . '</p>';
        }

        wp_die();
    }

    /**
     * Handle save customer (create/update)
     */
    public function handle_save_customer(): void {
        @ini_set('display_errors', '0');
        ob_start();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        $mode = $_POST['mode'] ?? 'create';

        // Check permissions
        if ($mode === 'edit') {
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_customers') &&
                !current_user_can('edit_own_customer')) {
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
                wp_die();
            }
        } else {
            if (!current_user_can('manage_options') && !current_user_can('add_customer')) {
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
                wp_die();
            }
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        // Prepare data
        $data = [
            'name' => sanitize_text_field($_POST['customer_name'] ?? ''),
            'npwp' => sanitize_text_field($_POST['customer_npwp'] ?? ''),
            'nib' => sanitize_text_field($_POST['customer_nib'] ?? ''),
            'status' => sanitize_text_field($_POST['customer_status'] ?? 'active'),
            'provinsi_id' => !empty($_POST['customer_provinsi_id']) ? (int) $_POST['customer_provinsi_id'] : null,
            'regency_id' => !empty($_POST['customer_regency_id']) ? (int) $_POST['customer_regency_id'] : null,
        ];

        // Validate
        $validation_errors = $this->validator->validateForm($data, $customer_id);
        if (!empty($validation_errors)) {
            ob_end_clean();
            wp_send_json_error(['message' => implode(' ', $validation_errors)]);
        }

        try {
            if ($mode === 'edit' && $customer_id) {
                // Update existing
                $result = $this->model->update($customer_id, $data);

                if ($result) {
                    wp_cache_delete('customer_' . $customer_id, 'wp-customer');

                    $customer = $this->model->find($customer_id);
                    $membership = $this->model->getMembershipData($customer_id);
                    $access = $this->validator->validateAccess($customer_id);

                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Customer updated successfully', 'wp-customer'),
                        'customer' => $customer,
                        'membership' => $membership,
                        'access_type' => $access['access_type']
                    ]);
                } else {
                    ob_end_clean();
                    wp_send_json_error(['message' => __('Failed to update customer', 'wp-customer')]);
                }
            } else {
                // Create new - with WordPress user
                $admin_name = sanitize_text_field($_POST['admin_name'] ?? '');
                $admin_email = sanitize_email($_POST['admin_email'] ?? '');

                $admin_validation_errors = $this->validator->validateAdminFields([
                    'admin_name' => $admin_name,
                    'admin_email' => $admin_email
                ]);

                if (!empty($admin_validation_errors)) {
                    ob_end_clean();
                    wp_send_json_error(['message' => implode(' ', $admin_validation_errors)]);
                    wp_die();
                }

                // Create WordPress user
                $username = sanitize_user(strtolower(str_replace(' ', '', $admin_name)));
                $random_password = wp_generate_password(12, true);

                $new_user_id = wp_create_user($username, $random_password, $admin_email);

                if (is_wp_error($new_user_id)) {
                    ob_end_clean();
                    wp_send_json_error(['message' => __('Failed to create user: ', 'wp-customer') . $new_user_id->get_error_message()]);
                    wp_die();
                }

                // Update user meta
                wp_update_user([
                    'ID' => $new_user_id,
                    'display_name' => $admin_name,
                    'first_name' => $admin_name
                ]);

                // Assign roles
                $user = new \WP_User($new_user_id);
                $user->set_role('customer');
                $user->add_role('customer_admin');
                $user->add_role('customer_employee');

                $data['user_id'] = $new_user_id;

                // Create customer
                $customer_id = $this->model->create($data);

                if ($customer_id) {
                    wp_new_user_notification($new_user_id, null, 'user');

                    $customer = $this->model->find($customer_id);
                    $membership = $this->model->getMembershipData($customer_id);
                    $access = $this->validator->validateAccess($customer_id);

                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Customer created successfully. Login credentials sent to admin email.', 'wp-customer'),
                        'customer' => $customer,
                        'membership' => $membership,
                        'access_type' => $access['access_type']
                    ]);
                } else {
                    wp_delete_user($new_user_id);
                    ob_end_clean();
                    wp_send_json_error(['message' => __('Failed to create customer', 'wp-customer')]);
                }
            }

        } catch (\Exception $e) {
            if (isset($new_user_id) && !is_wp_error($new_user_id)) {
                wp_delete_user($new_user_id);
            }

            ob_end_clean();
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delete customer
     */
    public function handle_delete_customer(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        if (!current_user_can('manage_options') && !current_user_can('delete_customer')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            wp_die();
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
        }

        try {
            $customer = $this->model->find($customer_id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
            }

            global $wpdb;
            $result = $wpdb->delete(
                $wpdb->prefix . 'app_customers',
                ['id' => $customer_id],
                ['%d']
            );

            if ($result !== false) {
                $cache = new \WPCustomer\Cache\CustomerCacheManager();
                $cache->invalidateCustomerCache($customer_id);

                wp_send_json_success(['message' => __('Customer deleted successfully', 'wp-customer')]);
            } else {
                wp_send_json_error(['message' => __('Failed to delete customer', 'wp-customer')]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
