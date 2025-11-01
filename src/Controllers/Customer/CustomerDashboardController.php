<?php
/**
 * Customer Dashboard Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Customer
 * @version     1.3.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Customer/CustomerDashboardController.php
 *
 * Description: Controller untuk Customer dashboard dengan base panel system.
 *              Registers hooks untuk DataTable, stats, dan tabs.
 *              Handles AJAX requests untuk panel content.
 *              Follows PlatformStaffDashboardController pattern dari wp-app-core.
 *
 * Dependencies:
 * - wp-app-core base panel system (DashboardTemplate)
 * - CustomerDataTableModel untuk DataTable processing
 * - BranchDataTableModel untuk branches DataTable
 * - EmployeeDataTableModel untuk employees DataTable
 * - CustomerModel untuk CRUD operations
 *
 * Changelog:
 * 1.3.0 - 2025-11-01 (TODO-2188)
 * - Added modal CRUD implementation
 * - Added handle_get_customer_form() - serves form HTML via AJAX
 * - Added handle_save_customer() - processes create/update
 * - Added handle_delete_customer() - handles customer deletion
 * - Integrated with wp-app-core centralized modal system
 *
 * 1.2.0 - 2025-11-01 (Review-02 Complete)
 * - Implemented BranchDataTableModel and EmployeeDataTableModel
 * - Updated handle_branches_datatable() to use BranchDataTableModel
 * - Updated handle_employees_datatable() to use EmployeeDataTableModel
 * - Branches and Employees tabs now show actual data
 *
 * 1.1.0 - 2025-11-01 (Review-02 from TODO-2187)
 * - Added branches tab with lazy-loaded DataTable
 * - Added employees tab with lazy-loaded DataTable
 * - Added AJAX handlers: load_branches_tab, load_employees_tab
 * - Added AJAX handlers: get_branches_datatable, get_employees_datatable
 * - Support 4 tabs: Info, Branches, Employees, Placeholder
 *
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following PlatformStaffDashboardController pattern
 * - Integrated with centralized DataTable system
 * - Register hooks for DataTable, stats, tabs
 * - Implement AJAX handlers
 * - Support 2 tabs: Info + Placeholder
 */

namespace WPCustomer\Controllers\Customer;

use WPCustomer\Models\Customer\CustomerDataTableModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchDataTableModel;
use WPCustomer\Models\Employee\EmployeeDataTableModel;
use WPCustomer\Validators\CustomerValidator;
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

class CustomerDashboardController {

    /**
     * @var CustomerDataTableModel DataTable model instance
     */
    private $datatable_model;

    /**
     * @var CustomerModel CRUD model instance
     */
    private $model;

    /**
     * @var CustomerValidator Validator instance
     */
    private $validator;

    /**
     * Constructor
     * Register all hooks for dashboard components
     */
    public function __construct() {
        $this->datatable_model = new CustomerDataTableModel();
        $this->model = new CustomerModel();
        $this->validator = new CustomerValidator();

        // Register hooks for dashboard components
        $this->register_hooks();
    }

    /**
     * Register all WordPress hooks
     */
    private function register_hooks(): void {
        // Panel content hook
        add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Page header hooks
        add_action('wpapp_page_header_left', [$this, 'render_header_title'], 10, 2);
        add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);

        // Stats cards hook
        add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);

        // Filter hooks
        add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

        // Statistics hook
        add_filter('wpapp_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Tabs hook
        add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Tab content injection hooks (V2: Only Info + Placeholder for simplicity)
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        // add_action('wpapp_tab_view_content', [$this, 'render_branches_tab'], 10, 3);  // Disabled for V2
        // add_action('wpapp_tab_view_content', [$this, 'render_employees_tab'], 10, 3);  // Disabled for V2
        add_action('wpapp_tab_view_content', [$this, 'render_placeholder_tab'], 10, 3);

        // AJAX handlers - Main DataTable
        add_action('wp_ajax_get_customer_datatable', [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_get_customer_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_customer_stats_v2', [$this, 'handle_get_stats']);  // V2: Different action name to avoid conflict with old menu

        // AJAX handlers - Lazy-load tabs (Disabled for V2)
        // add_action('wp_ajax_load_customer_branches_tab', [$this, 'handle_load_branches_tab']);
        // add_action('wp_ajax_load_customer_employees_tab', [$this, 'handle_load_employees_tab']);

        // AJAX handlers - DataTables in tabs (Disabled for V2)
        // add_action('wp_ajax_get_customer_branches_datatable', [$this, 'handle_branches_datatable']);
        // add_action('wp_ajax_get_customer_employees_datatable', [$this, 'handle_employees_datatable']);

        // AJAX handlers - Modal CRUD (Re-enabled for V2)
        add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
        add_action('wp_ajax_save_customer', [$this, 'handle_save_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'handle_delete_customer']);
    }

    /**
     * Render main dashboard page
     */
    public function renderDashboard(): void {
        // Render using centralized DashboardTemplate
        DashboardTemplate::render([
            'entity' => 'customer',
            'title' => __('WP Customer', 'wp-customer'),
            'ajax_action' => 'get_customer_details',
            'has_stats' => true,
            'has_tabs' => true,
        ]);
    }

    /**
     * Render DataTable HTML
     *
     * Hooked to: wpapp_left_panel_content
     *
     * @param array $config Configuration array
     */
    public function render_datatable($config): void {
        if (!is_array($config)) {
            return;
        }

        $entity = $config['entity'] ?? '';

        if ($entity !== 'customer') {
            return;
        }

        // Include DataTable view file
        $datatable_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/datatable.php';

        if (file_exists($datatable_file)) {
            include $datatable_file;
        }
    }

    /**
     * Render page header title
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_header_title($config, $entity): void {
        if ($entity !== 'customer') {
            return;
        }

        $this->render_partial('header-title', [], 'customer');
    }

    /**
     * Render page header buttons
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_header_buttons($config, $entity): void {
        if ($entity !== 'customer') {
            return;
        }

        $this->render_partial('header-buttons', [], 'customer');
    }

    /**
     * Render statistics header cards
     *
     * @param string $entity Entity name
     */
    public function render_header_cards($entity): void {
        if ($entity !== 'customer') {
            return;
        }

        // Get stats directly using DataTable model
        $total = $this->datatable_model->get_total_count('all');
        $active = $this->datatable_model->get_total_count('aktif');
        $inactive = $this->datatable_model->get_total_count('tidak aktif');

        // Render using partial template
        $this->render_partial('stat-cards', compact('total', 'active', 'inactive'), 'customer');
    }

    /**
     * Render filter controls
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_filters($config, $entity): void {
        if ($entity !== 'customer') {
            return;
        }

        // Include status filter partial
        $filter_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';

        if (file_exists($filter_file)) {
            include $filter_file;
        }
    }

    /**
     * Register statistics boxes
     *
     * @param array $stats Existing stats
     * @param string $entity Entity type
     * @return array Modified stats array
     */
    public function register_stats($stats, $entity) {
        if ($entity !== 'customer') {
            return $stats;
        }

        return [
            'total' => [
                'label' => __('Total Customers', 'wp-customer'),
                'value' => 0,  // Will be filled by AJAX
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

    /**
     * Register tabs for right panel
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity type
     * @return array Modified tabs array
     */
    public function register_tabs($tabs, $entity) {
        if ($entity !== 'customer') {
            return $tabs;
        }

        // V2: Only Info + Placeholder tabs (no lazy-load complexity)
        return [
            'info' => [
                'title' => __('Customer Information', 'wp-customer'),
                'priority' => 10,
                'lazy_load' => false
            ],
            'placeholder' => [
                'title' => __('Additional', 'wp-customer'),
                'priority' => 20,
                'lazy_load' => false
            ]
        ];
    }

    /**
     * Render info tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
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
     * Render placeholder tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_placeholder_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'placeholder') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/placeholder.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handle_datatable_ajax(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            // Get DataTable data using model
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error loading data', 'wp-customer')
            ]);
        }
    }

    /**
     * Handle get customer details AJAX request
     */
    public function handle_get_details(): void {
        try {
            check_ajax_referer('wpapp_panel_nonce', 'nonce');

            $customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if (!$customer_id) {
                throw new \Exception(__('Invalid customer ID', 'wp-customer'));
            }

            $customer = $this->model->find($customer_id);

            if (!$customer) {
                throw new \Exception(__('Customer not found', 'wp-customer'));
            }

            // Render tabs using hook-based pattern (like AgencyDashboardController)
            $tabs_content = $this->render_tab_contents($customer);

            wp_send_json_success([
                'title' => $customer->name,
                'tabs' => $tabs_content,
                'data' => $customer
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Render tab contents using hook-based pattern
     *
     * Follows AgencyDashboardController pattern
     *
     * @param object $customer Customer data
     * @return array Tab contents
     */
    private function render_tab_contents($customer): array {
        $tabs = [];

        // Get registered tabs
        $registered_tabs = apply_filters('wpapp_datatable_tabs', [], 'customer');

        foreach ($registered_tabs as $tab_id => $tab_config) {
            // Start output buffering
            ob_start();

            // Trigger hook - allows content injection via wpapp_tab_view_content
            // This will call render_info_tab(), render_branches_tab(), etc.
            do_action('wpapp_tab_view_content', $tab_id, 'customer', $customer);

            // Capture output
            $content = ob_get_clean();

            $tabs[$tab_id] = $content;
        }

        return $tabs;
    }

    /**
     * Handle get statistics AJAX request
     */
    public function handle_get_stats(): void {
        error_log('=== [CustomerDashboard] handle_get_stats() START ===');
        error_log('[CustomerDashboard] $_POST: ' . print_r($_POST, true));
        error_log('[CustomerDashboard] User ID: ' . get_current_user_id());
        error_log('[CustomerDashboard] Is user logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));

        try {
            error_log('[CustomerDashboard] handle_get_stats() - AJAX request received');

            // Verify nonce (use same method as save_customer for consistency)
            $nonce = $_POST['nonce'] ?? '';
            error_log('[CustomerDashboard] Nonce received: ' . $nonce);
            if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
                error_log('[CustomerDashboard] handle_get_stats() - Nonce check FAILED');
                wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
                return;
            }
            error_log('[CustomerDashboard] Nonce check PASSED');

            // Check permission - use same capability as viewing customer list
            if (!current_user_can('view_customer_list')) {
                error_log('[CustomerDashboard] handle_get_stats() - Permission denied');
                wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
                return;
            }

            error_log('[CustomerDashboard] handle_get_stats() - Getting counts...');
            $total = $this->datatable_model->get_total_count('all');
            $active = $this->datatable_model->get_total_count('active');
            $inactive = $this->datatable_model->get_total_count('inactive');

            error_log('[CustomerDashboard] handle_get_stats() - Results: Total=' . $total . ', Active=' . $active . ', Inactive=' . $inactive);

            wp_send_json_success([
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive
            ]);

        } catch (\Exception $e) {
            error_log('[CustomerDashboard] handle_get_stats() - Exception: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Render branches tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_branches_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'branches') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/branches.php';

        if (file_exists($tab_file)) {
            // Make customer data available as both $customer and $data
            $customer = $data;
            include $tab_file;
        }
    }

    /**
     * Render employees tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_employees_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'employees') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/employees.php';

        if (file_exists($tab_file)) {
            // Make customer data available as both $customer and $data
            $customer = $data;
            include $tab_file;
        }
    }

    /**
     * Handle load branches tab AJAX request
     *
     * AJAX action: load_branches_tab
     *
     * Lazy loads branches DataTable when tab is clicked
     */
    public function handle_load_branches_tab(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        // Get customer_id from AJAX request
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            // Generate branches DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-branches-datatable', compact('customer_id'), 'customer');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error loading branches', 'wp-customer')
            ]);
        }
    }

    /**
     * Handle load employees tab AJAX request
     *
     * AJAX action: load_employees_tab
     *
     * Lazy loads employees DataTable when tab is clicked
     */
    public function handle_load_employees_tab(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        // Get customer_id from AJAX request
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            // Generate employees DataTable HTML using template
            ob_start();
            $this->render_partial('ajax-employees-datatable', compact('customer_id'), 'customer');
            $html = ob_get_clean();

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error loading employees', 'wp-customer')
            ]);
        }
    }

    /**
     * Handle branches DataTable AJAX request
     *
     * AJAX action: get_customer_branches_datatable
     *
     * Called by DataTable initialization in branches tab
     * for server-side processing
     */
    public function handle_branches_datatable(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            // Create BranchDataTableModel instance
            $model = new BranchDataTableModel();

            // Get DataTable response
            $response = $model->get_datatable_data($_POST);

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading branches', 'wp-customer')]);
        }
    }

    /**
     * Handle employees DataTable AJAX request
     *
     * AJAX action: get_customer_employees_datatable
     *
     * Called by DataTable initialization in employees tab
     * for server-side processing
     */
    public function handle_employees_datatable(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_customer_list')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            return;
        }

        try {
            // Create EmployeeDataTableModel instance
            $model = new EmployeeDataTableModel();

            // Get DataTable response
            $response = $model->get_datatable_data($_POST);

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Error loading employees', 'wp-customer')]);
        }
    }

    /**
     * Render a partial template
     *
     * @param string $partial Partial name
     * @param array $data Data to pass to partial
     * @param string $context Context (entity) directory
     */
    private function render_partial(string $partial, array $data = [], string $context = ''): void {
        extract($data);

        $partial_path = WP_CUSTOMER_PATH . 'src/Views/' . $context . '/partials/' . $partial . '.php';

        if (file_exists($partial_path)) {
            include $partial_path;
        }
    }

    /**
     * Handle get customer form AJAX request
     *
     * Returns form HTML for modal (create or edit mode)
     * TODO-2188: Modal CRUD implementation
     *
     * @return void
     */
    public function handle_get_customer_form(): void {
        // Verify nonce - check both GET and POST for flexibility
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
            echo '<p class="error">' . __('Security check failed', 'wp-customer') . '</p>';
            wp_die();
        }

        $mode = $_GET['mode'] ?? 'create';
        $customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

        // Check permissions based on mode
        if ($mode === 'edit') {
            // For edit form, check edit permissions
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_customers') &&
                !current_user_can('edit_own_customer')) {
                echo '<p class="error">' . __('Permission denied. Required capability: edit_all_customers or edit_own_customer', 'wp-customer') . '</p>';
                wp_die();
            }
        } else {
            // For create form, check add permission
            if (!current_user_can('manage_options') && !current_user_can('add_customer')) {
                echo '<p class="error">' . __('Permission denied. Required capability: add_customer', 'wp-customer') . '</p>';
                wp_die();
            }
        }

        try {
            if ($mode === 'edit' && $customer_id) {
                // Get customer data
                $customer = $this->model->find($customer_id);

                if (!$customer) {
                    echo '<p class="error">' . __('Customer not found', 'wp-customer') . '</p>';
                    wp_die();
                }

                // Load edit form template
                include WP_CUSTOMER_PATH . 'src/Views/customer/forms/edit-customer-form.php';
            } else {
                // Load create form template
                include WP_CUSTOMER_PATH . 'src/Views/customer/forms/create-customer-form.php';
            }
        } catch (\Exception $e) {
            echo '<p class="error">' . esc_html($e->getMessage()) . '</p>';
        }

        wp_die();
    }

    /**
     * Handle save customer AJAX request
     *
     * Processes form submission (create or update)
     * TODO-2188: Modal CRUD implementation
     *
     * @return void
     */
    public function handle_save_customer(): void {
        // Suppress any output before JSON response
        @ini_set('display_errors', '0');

        // Start output buffering to catch any accidental output
        ob_start();

        // Verify nonce
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        $mode = $_POST['mode'] ?? 'create';

        // Check permissions based on mode
        if ($mode === 'edit') {
            // For edit, check edit_all_customers or edit_own_customer
            if (!current_user_can('manage_options') &&
                !current_user_can('edit_all_customers') &&
                !current_user_can('edit_own_customer')) {
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
                wp_die();
            }
        } else {
            // For create, check add_customer
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

        // Validate form data using validator
        $validation_errors = $this->validator->validateForm($data, $customer_id);
        if (!empty($validation_errors)) {
            ob_end_clean();
            wp_send_json_error([
                'message' => implode(' ', $validation_errors)
            ]);
        }

        try {
            if ($mode === 'edit' && $customer_id) {
                // Update existing
                $result = $this->model->update($customer_id, $data);

                if ($result) {
                    // Clear cache before getting fresh data
                    wp_cache_delete('customer_' . $customer_id, 'wp-customer');

                    // Get fresh customer data for response
                    $customer = $this->model->find($customer_id);
                    $membership = $this->model->getMembershipData($customer_id);
                    $access = $this->validator->validateAccess($customer_id);

                    // Clean output buffer and return complete data
                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Customer updated successfully', 'wp-customer'),
                        'customer' => $customer,
                        'membership' => $membership,
                        'access_type' => $access['access_type']
                    ]);
                } else {
                    ob_end_clean();
                    wp_send_json_error([
                        'message' => __('Failed to update customer', 'wp-customer')
                    ]);
                }
            } else {
                // Create new - create WordPress user first
                $admin_name = sanitize_text_field($_POST['admin_name'] ?? '');
                $admin_email = sanitize_email($_POST['admin_email'] ?? '');

                // Validate admin fields using validator
                $admin_validation_errors = $this->validator->validateAdminFields([
                    'admin_name' => $admin_name,
                    'admin_email' => $admin_email
                ]);

                if (!empty($admin_validation_errors)) {
                    ob_end_clean();
                    wp_send_json_error([
                        'message' => implode(' ', $admin_validation_errors)
                    ]);
                    wp_die();
                }

                // Create WordPress user
                $username = sanitize_user(strtolower(str_replace(' ', '', $admin_name)));
                $random_password = wp_generate_password(12, true);

                $new_user_id = wp_create_user($username, $random_password, $admin_email);

                if (is_wp_error($new_user_id)) {
                    ob_end_clean();
                    wp_send_json_error([
                        'message' => __('Failed to create user: ', 'wp-customer') . $new_user_id->get_error_message()
                    ]);
                    wp_die();
                }

                // Update user meta
                wp_update_user([
                    'ID' => $new_user_id,
                    'display_name' => $admin_name,
                    'first_name' => $admin_name
                ]);

                // Assign multiple roles: customer (base), customer_admin, customer_employee
                $user = new \WP_User($new_user_id);
                $user->set_role('customer'); // Primary role
                $user->add_role('customer_admin'); // Admin role
                $user->add_role('customer_employee'); // Employee role

                // Add user_id to customer data
                $data['user_id'] = $new_user_id;

                // Create customer
                $customer_id = $this->model->create($data);

                if ($customer_id) {
                    // Send email notification to admin
                    wp_new_user_notification($new_user_id, null, 'user');

                    // Get fresh customer data for response
                    $customer = $this->model->find($customer_id);
                    $membership = $this->model->getMembershipData($customer_id);
                    $access = $this->validator->validateAccess($customer_id);

                    // Clean buffer and return complete data for panel display
                    ob_end_clean();
                    wp_send_json_success([
                        'message' => __('Customer created successfully. Login credentials sent to admin email.', 'wp-customer'),
                        'customer' => $customer,
                        'membership' => $membership,
                        'access_type' => $access['access_type']
                    ]);
                } else {
                    // Rollback: delete user if customer creation failed
                    wp_delete_user($new_user_id);
                    ob_end_clean();
                    wp_send_json_error([
                        'message' => __('Failed to create customer', 'wp-customer')
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Rollback: delete user if customer creation failed with exception
            if (isset($new_user_id) && !is_wp_error($new_user_id)) {
                wp_delete_user($new_user_id);
                error_log('[CustomerDashboard] Rolled back user creation (ID: ' . $new_user_id . ') due to error: ' . $e->getMessage());
            }

            // Clean buffer and return error
            ob_end_clean();
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle delete customer AJAX request
     *
     * TODO-2188: Modal CRUD implementation
     *
     * @return void
     */
    public function handle_delete_customer(): void {
        // Verify nonce
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
            wp_die();
        }

        // Check permissions - delete_customer capability
        if (!current_user_can('manage_options') && !current_user_can('delete_customer')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
            wp_die();
        }

        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Invalid customer ID', 'wp-customer')]);
        }

        try {
            // Check if customer exists
            $customer = $this->model->find($customer_id);

            if (!$customer) {
                wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
            }

            // Delete customer (will also delete branches and employees via ON DELETE CASCADE)
            global $wpdb;
            $result = $wpdb->delete(
                $wpdb->prefix . 'app_customers',
                ['id' => $customer_id],
                ['%d']
            );

            if ($result !== false) {
                // Clear cache
                $cache = new \WPCustomer\Cache\CustomerCacheManager();
                $cache->invalidateCustomerCache($customer_id);

                wp_send_json_success([
                    'message' => __('Customer deleted successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to delete customer', 'wp-customer')
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
