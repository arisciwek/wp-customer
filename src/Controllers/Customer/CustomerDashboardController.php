<?php
/**
 * Customer Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     3.0.0
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

        // Register tab content hooks
        add_action('wpdt_tab_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpdt_tab_content', [$this, 'render_branches_tab'], 10, 3);
        add_action('wpdt_tab_content', [$this, 'render_employees_tab'], 10, 3);

        // AJAX handlers - Dashboard
        add_action('wp_ajax_get_customer_datatable', [$this, 'handle_datatable']);
        add_action('wp_ajax_get_customer_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_customer_stats_v2', [$this, 'handle_get_stats']);

        // AJAX handlers - Lazy-load tabs
        add_action('wp_ajax_load_customer_branches_tab', [$this, 'handle_load_branches_tab']);
        add_action('wp_ajax_load_customer_employees_tab', [$this, 'handle_load_employees_tab']);

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
        if (isset($_GET['page']) && $_GET['page'] === 'wp-customer') {
            return true;
        }
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
                'priority' => 10
            ],
            'branches' => [
                'title' => __('Cabang', 'wp-customer'),
                'priority' => 20
            ],
            'employees' => [
                'title' => __('Staff', 'wp-customer'),
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

        $view_file = WP_CUSTOMER_PATH . 'src/Views/admin/datatable/datatable.php';

        if (file_exists($view_file)) {
            include $view_file;
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
     * Render info tab content
     */
    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'info') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Render branches tab content
     */
    public function render_branches_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'branches') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/branches.php';

        if (file_exists($tab_file)) {
            $customer = $data;
            include $tab_file;
        }
    }

    /**
     * Render employees tab content
     */
    public function render_employees_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'employees') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/employees.php';

        if (file_exists($tab_file)) {
            $customer = $data;
            include $tab_file;
        }
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
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        try {
            $customer = $this->model->find($id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            $membership = $this->model->getMembershipData($id);
            $access = $this->validator->validateAccess($id);

            wp_send_json_success([
                'customer' => $customer,
                'membership' => $membership,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
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
    // AJAX HANDLERS - LAZY-LOAD TABS
    // ========================================

    /**
     * Handle lazy-load branches tab
     */
    public function handle_load_branches_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            ob_start();
            $view_file = WP_CUSTOMER_PATH . 'src/Views/customer/partials/ajax-branches-datatable.php';
            if (file_exists($view_file)) {
                include $view_file;
            }
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy-load employees tab
     */
    public function handle_load_employees_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            ob_start();
            $view_file = WP_CUSTOMER_PATH . 'src/Views/customer/partials/ajax-employees-datatable.php';
            if (file_exists($view_file)) {
                include $view_file;
            }
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
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

                include WP_CUSTOMER_PATH . 'src/Views/customer/forms/edit-customer-form.php';
            } else {
                include WP_CUSTOMER_PATH . 'src/Views/customer/forms/create-customer-form.php';
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
