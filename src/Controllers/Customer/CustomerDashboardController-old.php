<?php
/**
 * Customer Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Customer/CustomerDashboardController.php
 *
 * Description: Dashboard controller untuk Customer management.
 *              Extends AbstractDashboardController untuk inherit dashboard system.
 *              Handles DataTable rendering, statistics, filters, 3 tabs, dan modal CRUD.
 *              Integrates dengan centralized base panel system.
 *
 * Changelog:
 * 2.0.0 - 2025-11-04 (TODO-1198: Abstract Controller Implementation)
 * - BREAKING: Refactored to extend AbstractDashboardController
 * - Code reduction: 1005 lines → ~600 lines (40% reduction)
 * - ALL HOOKS PRESERVED (19 hooks: 8 actions + 2 filters + 9 AJAX)
 * - Implements 13 abstract methods from base class
 * - All dashboard rendering inherited FREE from AbstractDashboardController
 * - Custom methods preserved: modal CRUD, lazy-load tabs, branches/employees DataTables
 * - Maintains 3 tabs: Info, Branches (Cabang), Employees (Staff)
 *
 * 1.4.0 - 2025-11-02
 * - Added Branches and Employees tabs
 * - Enabled lazy-loading for tabs
 *
 * 1.3.0 - 2025-11-01
 * - Added modal CRUD implementation
 *
 * 1.0.0 - 2025-11-01
 * - Initial implementation
 */

namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractDashboardController;
use WPCustomer\Models\Customer\CustomerDataTableModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchDataTableModel;
use WPCustomer\Models\Employee\EmployeeDataTableModel;
use WPCustomer\Validators\CustomerValidator;

defined('ABSPATH') || exit;

class CustomerDashboardController extends AbstractDashboardController {

    /**
     * @var CustomerValidator
     */
    private $validator;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->validator = new CustomerValidator();

        // Register tab content hooks (PRESERVED - lines 123-125)
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_branches_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_employees_tab'], 10, 3);

        // AJAX handlers - Lazy-load tabs (PRESERVED - lines 133-134)
        add_action('wp_ajax_load_customer_branches_tab', [$this, 'handle_load_branches_tab']);
        add_action('wp_ajax_load_customer_employees_tab', [$this, 'handle_load_employees_tab']);

        // AJAX handlers - DataTables in tabs (PRESERVED - lines 137-138)
        add_action('wp_ajax_get_customer_branches_datatable', [$this, 'handle_branches_datatable']);
        add_action('wp_ajax_get_customer_employees_datatable', [$this, 'handle_employees_datatable']);

        // AJAX handlers - Modal CRUD (PRESERVED - lines 141-143)
        add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
        add_action('wp_ajax_save_customer', [$this, 'handle_save_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (13 required)
    // ========================================

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityDisplayName(): string {
        return 'Customer';
    }

    protected function getEntityDisplayNamePlural(): string {
        return 'Customers';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getEntityPath(): string {
        return WP_CUSTOMER_PATH;
    }

    protected function getDataTableModel() {
        return new CustomerDataTableModel();
    }

    protected function getModel() {
        return new CustomerModel();
    }

    protected function getDataTableAjaxAction(): string {
        return 'get_customer_datatable';
    }

    protected function getDetailsAjaxAction(): string {
        return 'get_customer_details';
    }

    protected function getStatsAjaxAction(): string {
        return 'get_customer_stats_v2';
    }

    protected function getViewCapability(): string {
        return 'view_customer_list';
    }

    protected function getStatsConfig(): array {
        return [
            'total' => [
                'label' => __('Total Customers', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-businessperson',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ]
        ];
    }

    protected function getTabsConfig(): array {
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
    // TAB CONTENT RENDERING (PRESERVED)
    // ========================================

    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'info') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

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
    // LAZY-LOAD TAB HANDLERS (PRESERVED)
    // ========================================

    public function handle_load_branches_tab(): void {
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
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
            $this->render_partial('ajax-branches-datatable', compact('customer_id'), 'customer');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches', 'wp-customer')]);
        }
    }

    public function handle_load_employees_tab(): void {
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
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
            $this->render_partial('ajax-employees-datatable', compact('customer_id'), 'customer');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
        }
    }

    // ========================================
    // DATATABLES IN TABS (PRESERVED)
    // ========================================

    public function handle_branches_datatable(): void {
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
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

    public function handle_employees_datatable(): void {
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
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
    // MODAL CRUD HANDLERS (PRESERVED - TODO-2188)
    // ========================================

    public function handle_get_customer_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
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

    public function handle_save_customer(): void {
        @ini_set('display_errors', '0');
        ob_start();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
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
            'province_id' => !empty($_POST['customer_province_id']) ? (int) $_POST['customer_province_id'] : null,
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

    public function handle_delete_customer(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
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

    // ========================================
    // CUSTOM STATISTICS OVERRIDE
    // ========================================

    protected function getStatsData(): array {
        return [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ];
    }
}
