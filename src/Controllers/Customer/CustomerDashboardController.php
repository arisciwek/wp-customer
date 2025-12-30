<?php
/**
 * Customer Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     4.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Customer/CustomerDashboardController.php
 *
 * Description: Dashboard controller untuk Customer management.
 *              Now extends AbstractDashboardController from wp-datatable.
 *              Significantly reduced code by using base class functionality.
 *
 * Changelog:
 * 4.0.0 - 2025-12-30
 * - REFACTOR: Extends AbstractDashboardController from wp-datatable
 * - Implemented 7 abstract methods for base functionality
 * - Removed duplicate code (render, signal_dual_panel, register_tabs, etc)
 * - Kept custom methods (CRUD, tab datatables, lazy loading)
 * - Override handle_get_details() for custom logic (membership, admin, head office)
 * - Override render_statistics() for custom icon (dashicons-businessperson)
 * - Code reduction: ~822 lines → ~500 lines (39% reduction)
 *
 * 3.0.3 - 2025-12-25
 * - Added head office data to customer detail response
 *
 * 3.0.2 - 2025-12-25
 * - Added admin user data to customer detail response
 *
 * 3.0.1 - 2025-11-09
 * - Implemented proper wp-datatable tab rendering pattern
 *
 * 3.0.0 - 2025-11-09
 * - Migrated to wp-datatable DualPanel layout system
 */

namespace WPCustomer\Controllers\Customer;

use WPDataTable\Core\AbstractDashboardController;
use WPCustomer\Models\Customer\CustomerDataTableModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchDataTableModel;
use WPCustomer\Models\Employee\EmployeeDataTableModel;
use WPCustomer\Validators\CustomerValidator;

defined('ABSPATH') || exit;

class CustomerDashboardController extends AbstractDashboardController {

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new CustomerModel();
        $this->datatable_model = new CustomerDataTableModel();
        $this->validator = new CustomerValidator();

        $this->init_hooks();
    }

    // ========================================
    // ABSTRACT METHODS IMPLEMENTATION
    // ========================================

    /**
     * Get entity name
     */
    protected function getEntity(): string {
        return 'customer';
    }

    /**
     * Get page slug
     */
    protected function getPageSlug(): string {
        return 'wp-customer';
    }

    /**
     * Get required capability
     */
    protected function getCapability(): string {
        return 'view_customer_list';
    }

    /**
     * Get dashboard configuration
     */
    protected function getDashboardConfig(): array {
        return [
            'entity' => 'customer',
            'title' => __('Customers', 'wp-customer'),
            'description' => __('Manage your customers', 'wp-customer'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_customer_details',
        ];
    }

    /**
     * Get tabs configuration
     */
    protected function getTabsConfig(): array {
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
            ],
            'history' => [
                'title' => __('History', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/templates/audit-log/history-tab.php',
                'priority' => 40
            ]
        ];
    }

    /**
     * Get DataTable view path
     */
    protected function getDataTableViewPath(): string {
        return WP_CUSTOMER_PATH . 'src/Views/admin/customer/datatable/datatable.php';
    }

    /**
     * Get detail data by ID
     */
    protected function getDetailData(int $id): ?object {
        return $this->model->find($id);
    }

    // ========================================
    // OVERRIDE PARENT METHODS
    // ========================================

    /**
     * Initialize hooks
     * Override to add custom AJAX handlers
     */
    protected function init_hooks(): void {
        // Call parent to register base hooks
        parent::init_hooks();

        // Add custom AJAX handlers - Tab lazy loading
        add_action('wp_ajax_load_customer_branches_tab', [$this, 'handle_load_branches_tab']);
        add_action('wp_ajax_load_customer_employees_tab', [$this, 'handle_load_employees_tab']);

        // Add custom AJAX handlers - DataTables in tabs
        add_action('wp_ajax_get_customer_branches_datatable', [$this, 'handle_branches_datatable']);
        add_action('wp_ajax_get_customer_employees_datatable', [$this, 'handle_employees_datatable']);

        // Add custom AJAX handlers - Modal CRUD
        add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
        add_action('wp_ajax_save_customer', [$this, 'handle_save_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);

        // Override stats handler to use v2
        remove_action('wp_ajax_get_customer_stats', [$this, 'handle_get_stats']);
        add_action('wp_ajax_get_customer_stats_v2', [$this, 'handle_get_stats']);
    }

    /**
     * Override stats labels for custom text
     */
    protected function getStatsLabels(): array {
        return [
            'total' => __('Total Customers', 'wp-customer'),
            'active' => __('Active', 'wp-customer'),
            'inactive' => __('Inactive', 'wp-customer')
        ];
    }

    /**
     * Override render_statistics for custom icon
     */
    public function render_statistics($config): void {
        if ($config['entity'] !== $this->getEntity()) {
            return;
        }

        $stats = [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ];

        $labels = $this->getStatsLabels();

        ?>
        <div class="wpdt-stat-box">
            <div class="wpdt-stat-icon dashicons dashicons-businessperson"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['total']); ?></div>
                <div class="wpdt-stat-label"><?php echo esc_html($labels['total']); ?></div>
            </div>
        </div>

        <div class="wpdt-stat-box wpdt-stat-success">
            <div class="wpdt-stat-icon dashicons dashicons-yes-alt"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['active']); ?></div>
                <div class="wpdt-stat-label"><?php echo esc_html($labels['active']); ?></div>
            </div>
        </div>

        <div class="wpdt-stat-box wpdt-stat-danger">
            <div class="wpdt-stat-icon dashicons dashicons-dismiss"></div>
            <div class="wpdt-stat-content">
                <div class="wpdt-stat-value"><?php echo esc_html($stats['inactive']); ?></div>
                <div class="wpdt-stat-label"><?php echo esc_html($labels['inactive']); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Override handle_get_details for custom logic
     * Includes membership data, admin user, and head office
     */
    public function handle_get_details(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
            return;
        }

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        try {
            // Get customer data
            $customer = $this->getDetailData($id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            // Get additional customer-specific data
            $membership = $this->model->getMembershipData($id);
            $access = $this->validator->validateAccess($id);
            $admin_user = $this->model->getAdminUser($id);
            $head_office = $this->model->getHeadOffice($id);

            // Add admin user data to customer object
            if ($admin_user) {
                $customer->admin_name = $admin_user->display_name;
                $customer->admin_email = $admin_user->user_email;
                $customer->admin_login = $admin_user->user_login;
            }

            // Add head office data to customer object
            if ($head_office) {
                $customer->pusat_name = $head_office->name;
                $customer->pusat_code = $head_office->code;
                $customer->pusat_address = $head_office->address;
                $customer->pusat_postal_code = $head_office->postal_code;
                $customer->pusat_phone = $head_office->phone;
                $customer->pusat_email = $head_office->email;
                $customer->pusat_latitude = $head_office->latitude;
                $customer->pusat_longitude = $head_office->longitude;
                $customer->province_name = $head_office->province_name;
                $customer->regency_name = $head_office->regency_name;
            }

            // Render tabs content
            $tabs = $this->render_tabs_content($customer);

            wp_send_json_success([
                'customer' => $customer,
                'membership' => $membership,
                'access_type' => $access['access_type'],
                'title' => $customer->name,
                'tabs' => $tabs
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // CUSTOM AJAX HANDLERS - DATATABLES IN TABS
    // ========================================

    /**
     * Handle branches DataTable AJAX
     */
    public function handle_branches_datatable(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
            return;
        }

        if (!current_user_can($this->getCapability())) {
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
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
            return;
        }

        if (!current_user_can($this->getCapability())) {
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
    // CUSTOM AJAX HANDLERS - TAB LAZY LOADING
    // ========================================

    /**
     * Handle lazy load branches tab content
     */
    public function handle_load_branches_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
            return;
        }

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
            return;
        }

        try {
            $customer = $this->model->find($customer_id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/partials/branches-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches tab', 'wp-customer')]);
        }
    }

    /**
     * Handle lazy load employees tab content
     */
    public function handle_load_employees_tab(): void {
        if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
            return;
        }

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
            return;
        }

        try {
            $customer = $this->model->find($customer_id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                return;
            }

            ob_start();
            include WP_CUSTOMER_PATH . 'src/Views/admin/customer/tabs/partials/employees-content.php';
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees tab', 'wp-customer')]);
        }
    }

    // ========================================
    // CUSTOM AJAX HANDLERS - MODAL CRUD
    // ========================================

    /**
     * Handle get customer form (create/edit)
     */
    public function handle_get_customer_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            echo '<p class="error">' . $this->getSecurityFailedMessage() . '</p>';
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
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
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

    /**
     * Handle delete customer
     */
    public function handle_delete_customer(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            wp_send_json_error(['message' => $this->getSecurityFailedMessage()]);
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
            $result = $this->model->delete($customer_id);

            if ($result) {
                wp_send_json_success(['message' => __('Customer deleted successfully', 'wp-customer')]);
            } else {
                wp_send_json_error(['message' => __('Customer not found or failed to delete', 'wp-customer')]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
