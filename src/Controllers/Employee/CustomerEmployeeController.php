<?php
/**
 * Customer Employee Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Employee
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Employee/CustomerEmployeeController.php
 *
 * Description: Controller untuk mengelola data karyawan customer.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles HTTP requests, delegates to Model/Validator.
 *
 * Changelog:
 * 2.0.0 - 2025-11-09 (TODO-2194: CRUD Refactoring)
 * - BREAKING: Refactored to extend AbstractCrudController
 * - Implements 9 abstract methods
 * - Uses EmployeeCacheManager (not CustomerCacheManager)
 * - CRUD methods INHERITED: store(), update(), delete(), show()
 * - Preserved: All AJAX handlers
 * - Preserved: User creation and role assignment logic
 * - Preserved: Department handling (finance, operation, legal, purchase)
 * - Preserved: Status management and permission checks
 *
 * 1.0.11 - 2024-01-12
 * - Initial release
 * - Added CRUD operations
 * - Added DataTable integration
 * - Added permission handling
 *
 * Previous version: CustomerEmployeeController-OLD-*.php (backup)
 */

namespace WPCustomer\Controllers\Employee;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Validators\Employee\CustomerEmployeeValidator;
use WPCustomer\Cache\EmployeeCacheManager;

class CustomerEmployeeController extends AbstractCrudController {
    private CustomerModel $customerModel;
    private CustomerEmployeeModel $model;
    private CustomerEmployeeValidator $validator;
    private EmployeeCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/employee.log';

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->model = new CustomerEmployeeModel();
        $this->validator = new CustomerEmployeeValidator();
        $this->cache = EmployeeCacheManager::getInstance();

        // Initialize log file in plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX endpoints
        add_action('wp_ajax_handle_customer_employee_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer_employee', [$this, 'show']);
        add_action('wp_ajax_create_customer_employee', [$this, 'store']);
        add_action('wp_ajax_update_customer_employee', [$this, 'update']);
        add_action('wp_ajax_delete_customer_employee', [$this, 'delete']);
        add_action('wp_ajax_change_customer_employee_status', [$this, 'changeStatus']);
        add_action('wp_ajax_create_customer_employee_button', [$this, 'createEmployeeButton']);

        // Modal integration (auto-wire system)
        add_action('wp_ajax_get_employee_form', [$this, 'handle_get_employee_form']);
        add_action('wp_ajax_save_employee', [$this, 'handle_save_employee']);
        add_action('wp_ajax_delete_employee', [$this, 'handle_delete_employee']);

        // Hook untuk menampilkan field tambahan di profil user
        add_action('show_user_profile', [$this, 'showProfileExtras']);
        add_action('edit_user_profile', [$this, 'showProfileExtras']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'employee';
    }

    /**
     * Get entity name plural
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'employees';
    }

    /**
     * Get nonce action
     *
     * @return string
     */
    protected function getNonceAction(): string {
        return 'wp_customer_nonce';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get validator instance
     *
     * @return CustomerEmployeeValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return CustomerEmployeeModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get cache group
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_customer_employee';
    }

    /**
     * Prepare data from $_POST for create operation
     *
     * Handles:
     * - Customer ID and Branch ID validation
     * - Permission check
     * - Field sanitization (name, email, position, phone, etc.)
     * - Department flags (finance, operation, legal, purchase)
     * - User creation with WordPress user_id
     * - Role assignment (customer + customer_employee)
     *
     * @return array Sanitized data
     * @throws \Exception On validation or permission failure
     */
    protected function prepareCreateData(): array {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

        if (!$customer_id) {
            throw new \Exception('ID Customer tidak valid');
        }

        // Check permission
        if (!$this->validator->canCreateEmployee($customer_id, $branch_id)) {
            throw new \Exception('Anda tidak memiliki izin untuk menambah karyawan');
        }

        // Sanitize input
        $data = [
            'customer_id' => $customer_id,
            'branch_id' => $branch_id,
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
            'operation' => isset($_POST['operation']) && $_POST['operation'] === "1",
            'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
            'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
            'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'created_by' => get_current_user_id(),
            'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive'])
                ? $_POST['status']
                : 'active'
        ];

        // Validate employee creation data
        $create_errors = $this->validator->validateCreate($data);
        if (!empty($create_errors)) {
            throw new \Exception(implode(', ', $create_errors));
        }

        // Create WordPress user for employee
        $user_data = [
            'user_login' => strstr($data['email'], '@', true) ?: sanitize_user(strtolower(str_replace(' ', '', $data['name']))),
            'user_email' => $data['email'],
            'first_name' => explode(' ', $data['name'], 2)[0],
            'last_name' => explode(' ', $data['name'], 2)[1] ?? '',
            'user_pass' => wp_generate_password(),
            'role' => 'customer'
        ];

        /**
         * Filter user data before creating WordPress user for employee
         *
         * Allows modification of user data before wp_insert_user() call.
         *
         * Use cases:
         * - Demo data: Force static IDs for predictable test data
         * - Migration: Import users with preserved IDs from external system
         * - Testing: Unit tests with predictable user IDs
         * - Custom user data: Add custom fields or metadata
         *
         * @param array $user_data User data for wp_insert_user()
         * @param array $data Original employee data from controller
         * @param string $context Context identifier ('employee')
         * @return array Modified user data
         *
         * @since 1.0.0
         */
        $user_data = apply_filters(
            'wp_customer_employee_user_before_insert',
            $user_data,
            $data,
            'employee'
        );

        // Handle static ID if requested (e.g., by demo data)
        $static_user_id = null;
        if (isset($user_data['ID'])) {
            $static_user_id = $user_data['ID'];
            unset($user_data['ID']); // wp_insert_user() doesn't accept ID parameter
        }

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            throw new \Exception($user_id->get_error_message());
        }

        // If static ID was requested, update the user ID
        if ($static_user_id !== null && $static_user_id != $user_id) {
            global $wpdb;

            // Check if target ID already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE ID = %d",
                $static_user_id
            ));

            if (!$existing) {
                // Update to static ID
                $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

                $result_users = $wpdb->update(
                    $wpdb->users,
                    ['ID' => $static_user_id],
                    ['ID' => $user_id],
                    ['%d'],
                    ['%d']
                );

                $result_meta = $wpdb->update(
                    $wpdb->usermeta,
                    ['user_id' => $static_user_id],
                    ['user_id' => $user_id],
                    ['%d'],
                    ['%d']
                );

                $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

                if ($result_users !== false && $result_meta !== false) {
                    $user_id = $static_user_id;
                    error_log("[EmployeeController] Updated user ID to static ID: {$static_user_id}");
                } else {
                    error_log("[EmployeeController] Failed to update to static ID: " . $wpdb->last_error);
                }
            } else {
                error_log("[EmployeeController] Static ID {$static_user_id} already exists, using auto ID {$user_id}");
            }
        }

        // Task-2170 Review-01: Assign multiple roles via direct wp_capabilities update
        // This prevents duplicate wp_capabilities entries that occur with add_role()
        // Dual-role pattern: base 'customer' + specific 'customer_employee'
        global $wpdb;

        // Ensure customer_employee role exists
        if (!get_role('customer_employee')) {
            add_role('customer_employee', __('Customer Employee', 'wp-customer'), []);
        }

        // Update wp_capabilities with both roles in a single entry
        $wpdb->update(
            $wpdb->usermeta,
            ['meta_value' => serialize(['customer' => true, 'customer_employee' => true])],
            ['user_id' => $user_id, 'meta_key' => 'wp_capabilities'],
            ['%s'],
            ['%d', '%s']
        );

        // Clear user cache to ensure roles are loaded fresh
        wp_cache_delete($user_id, 'user_meta');
        clean_user_cache($user_id);

        $data['user_id'] = $user_id;

        return $data;
    }

    /**
     * Prepare data from $_POST for update operation
     *
     * Handles:
     * - Employee ID validation
     * - Permission check
     * - Field sanitization
     * - Department flags (finance, operation, legal, purchase)
     *
     * @param int $id Entity ID
     * @return array Sanitized data
     * @throws \Exception On validation or permission failure
     */
    protected function prepareUpdateData(int $id): array {
        // Get existing employee data
        $employee = $this->model->find($id);
        if (!$employee) {
            throw new \Exception('Employee not found');
        }

        // Get customer data untuk permission check
        $customer = $this->customerModel->find($employee->customer_id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Permission check di awal
        if (!$this->validator->canEditEmployee($employee, $customer)) {
            throw new \Exception('Anda tidak memiliki izin untuk mengedit karyawan ini.');
        }

        // Sanitize input
        $data = [
            'customer_id' => $employee->customer_id, // Use existing customer_id (not editable)
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'branch_id' => isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0,
            'finance' => isset($_POST['finance']) && $_POST['finance'] === "1",
            'operation' => isset($_POST['operation']) && $_POST['operation'] === "1",
            'legal' => isset($_POST['legal']) && $_POST['legal'] === "1",
            'purchase' => isset($_POST['purchase']) && $_POST['purchase'] === "1",
            'keterangan' => sanitize_text_field($_POST['keterangan'] ?? ''),
            'updated_by' => get_current_user_id(),
            'status' => isset($_POST['status']) && in_array($_POST['status'], ['active', 'inactive'])
                ? $_POST['status']
                : 'active'
        ];

        // Validate employee update data
        $errors = $this->validator->validateUpdate($data, $id);
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        return $data;
    }

    // ========================================
    // OVERRIDE PARENT METHODS (Custom implementation)
    // ========================================

    /**
     * Override store() to handle user creation rollback on failure
     *
     * @return void
     */
    public function store(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Prepare data (includes user creation logic)
            $data = $this->prepareCreateData();

            // Create via model
            $employee_id = $this->model->create($data);
            if (!$employee_id) {
                // Rollback user creation if employee creation failed
                if (!empty($data['user_id'])) {
                    wp_delete_user($data['user_id']);
                }
                throw new \Exception('Failed to create employee');
            }

            // Send user notification email
            wp_new_user_notification($data['user_id'], null, 'user');

            // Send success response
            wp_send_json_success([
                'message' => __('Karyawan berhasil ditambahkan dan email aktivasi telah dikirim', 'wp-customer'),
                'employee' => $this->model->find($employee_id)
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'create');
        }
    }

    /**
     * Override show() to include customer data validation
     *
     * @return void
     */
    public function show(): void {
        try {
            error_log('WP Customer Employee Debug - show called for ID: ' . ($_POST['id'] ?? 'not set'));

            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Get employee data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            // Get customer data
            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Permission check
            $canView = $this->validator->canViewEmployee($employee, $customer);
            error_log('WP Customer Employee Debug - canViewEmployee result: ' . ($canView ? 'true' : 'false'));

            if (!$canView) {
                throw new \Exception('Anda tidak memiliki izin untuk melihat detail karyawan ini.');
            }

            // Validate view permission
            $errors = $this->validator->validateView($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Check cache
            $cached_employee = $this->cache->getEmployee($id);
            if (!$cached_employee) {
                $this->cache->setEmployee($id, $employee);
            } else {
                $employee = $cached_employee;
            }

            error_log('WP Customer Employee Debug - show success for employee ID: ' . $id);
            wp_send_json_success($employee);

        } catch (\Exception $e) {
            error_log('WP Customer Employee Debug - show error: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Override update() to include employee-specific logic
     *
     * @return void
     */
    public function update(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Prepare data (includes permission check)
            $data = $this->prepareUpdateData($id);

            // Update via model
            if (!$this->model->update($id, $data)) {
                throw new \Exception('Failed to update employee');
            }

            // Get updated employee
            $employee = $this->model->find($id);

            wp_send_json_success([
                'message' => __('Data karyawan berhasil diperbarui', 'wp-customer'),
                'employee' => $employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Override delete() to include employee-specific validation
     *
     * @return void
     */
    public function delete(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Get employee and customer for permission check
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Check delete permission
            if (!$this->validator->canDeleteEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk menghapus karyawan ini.');
            }

            // Validate delete
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Proceed with deletion
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete employee');
            }

            wp_send_json_success([
                'message' => __('Karyawan berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // CUSTOM AJAX HANDLERS (Entity-specific)
    // ========================================

    /**
     * Handle DataTable AJAX request (cache handled in Model)
     */
    public function handleDataTableRequest() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            // Get and validate parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;

            if (!$customer_id) {
                throw new \Exception('Customer ID is required');
            }

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Map numeric column index to column name
            $columns = ['name', 'department', 'branch_name', 'status', 'actions'];
            $orderColumnName = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

            // Get data from model (cache handled in model)
            $result = $this->model->getDataTableData(
                $customer_id,
                $start,
                $length,
                $search,
                $orderColumnName,
                $orderDir
            );

            if (!$result) {
                throw new \Exception('No data returned from model');
            }

            // Format data with validation
            $data = [];
            foreach ($result['data'] as $employee) {
                // Get customer for permission check
                $customer = $this->customerModel->find($employee->customer_id);
                if (!$this->validator->canViewEmployee($employee, $customer)) {
                    continue;
                }

                $data[] = [
                    'id' => $employee->id,
                    'name' => esc_html($employee->name),
                    'position' => esc_html($employee->position),
                    'department' => $this->generateDepartmentsBadges([
                        'finance' => (bool)$employee->finance,
                        'operation' => (bool)$employee->operation,
                        'legal' => (bool)$employee->legal,
                        'purchase' => (bool)$employee->purchase
                    ]),
                    'email' => esc_html($employee->email),
                    'branch_name' => esc_html($employee->branch_name),
                    'status' => $employee->status,
                    'actions' => $this->generateActionButtons($employee)
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $data
            ];

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Change employee status
     */
    public function changeStatus() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            if (!$this->validator->canEditEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk mengubah status karyawan ini.');
            }

            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status');
            }

            if (!$this->model->changeStatus($id, $status)) {
                throw new \Exception('Failed to update employee status');
            }

            // Get updated employee
            $employee = $this->model->find($id);
            wp_send_json_success([
                'message' => __('Status karyawan berhasil diperbarui', 'wp-customer'),
                'employee' => $employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Create employee button permission check
     */
    public function createEmployeeButton() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;

            if (!$customer_id) {
                throw new \Exception('ID Customer tidak valid');
            }

            $validator = new CustomerEmployeeValidator();
            $canCreate = $validator->canCreateEmployee($customer_id, $branch_id);

            if ($canCreate) {
                $button = '<button type="button" class="button button-primary" id="add-employee-btn">';
                $button .= '<span class="dashicons dashicons-plus-alt"></span>';
                $button .= __('Tambah Karyawan', 'wp-customer');
                $button .= '</button>';
            } else {
                $button = '';
            }

            wp_send_json_success([
                'button' => $button
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Get Employee Form - Modal Integration (TODO-2191)
     *
     * Serves create/edit employee form for wpAppModal centralized system.
     * Called via AJAX when Add/Edit employee button clicked.
     *
     * @since 1.0.0 (TODO-2191)
     * @return void Outputs form HTML and dies
     */
    public function handle_get_employee_form() {
        try {
            // Auto-wire system sends wpdt_nonce
            check_ajax_referer('wpdt_nonce', 'nonce');

            $employee_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $customer_id = isset($_GET['customer_id']) ? (int) $_GET['customer_id'] : 0;

            if ($employee_id) {
                // Edit mode
                $employee = $this->model->find($employee_id);
                if (!$employee) {
                    wp_send_json_error(['message' => __('Employee not found', 'wp-customer')]);
                    return;
                }

                $customer = $this->customerModel->find($employee->customer_id);
                if (!$customer) {
                    wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                    return;
                }

                // Permission check
                if (!$this->validator->canEditEmployee($employee, $customer)) {
                    wp_send_json_error(['message' => __('You do not have permission to edit this employee', 'wp-customer')]);
                    return;
                }

                // Load edit form and capture output for auto-wire system
                ob_start();
                include WP_CUSTOMER_PATH . 'src/Views/admin/employee/forms/edit-employee-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            } else {
                // Create mode
                if (!$customer_id) {
                    wp_send_json_error(['message' => __('Customer ID required', 'wp-customer')]);
                    return;
                }

                // Permission check
                if (!current_user_can('manage_options') && !current_user_can('add_customer_employee')) {
                    wp_send_json_error(['message' => __('You do not have permission to create employee', 'wp-customer')]);
                    return;
                }

                $customer = $this->customerModel->find($customer_id);
                if (!$customer) {
                    wp_send_json_error(['message' => __('Customer not found', 'wp-customer')]);
                    return;
                }

                // Load create form and capture output for auto-wire system
                ob_start();
                include WP_CUSTOMER_PATH . 'src/Views/admin/employee/forms/create-employee-form.php';
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Show profile extras
     */
    public function showProfileExtras($user) {
        $employeeData = $this->model->getByUserId($user->ID);

        // Debug ke error_log
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('=== [CustomerEmployeeController] showProfileExtras() ===');
            error_log('User ID: ' . $user->ID);
            error_log('Employee Data: ' . print_r($employeeData, true));
        }

        $user_roles = $user->roles;
        $user_capabilities = array_keys(array_filter($user->allcaps));

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-employee/partials/_customer_employee_profile_fields.php';
    }

    // ========================================
    // UTILITY METHODS (Private helpers)
    // ========================================

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $customer_base_dir = $upload_dir['basedir'] . '/wp-customer';
        $customer_log_dir = $customer_base_dir . '/logs';

        // Update log file path with monthly rotation
        $this->log_file = $customer_log_dir . '/employee-' . date('Y-m') . '.log';

        // Create directories if needed
        if (!file_exists($customer_base_dir)) {
            wp_mkdir_p($customer_base_dir);
        }

        if (!file_exists($customer_log_dir)) {
            wp_mkdir_p($customer_log_dir);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
            chmod($this->log_file, 0644);
        }
    }

    /**
     * Log debug messages
     */
    private function debug_log($message): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";
        error_log($log_message, 3, $this->log_file);
    }

    /**
     * Generate HTML for department badges
     */
    private function generateDepartmentsBadges(array $departments): string {
        // Check if any department is true
        $has_departments = array_filter($departments);
        if (empty($has_departments)) {
            return '<div class="department-badges-container empty">-</div>';
        }

        $badges = [];
        foreach ($departments as $dept => $active) {
            if ($active) {
                $label = ucfirst($dept); // Convert finance to Finance, etc.
                $badges[] = sprintf(
                    '<span class="department-badge %s">%s</span>',
                    esc_attr($dept),
                    esc_html($label)
                );
            }
        }

        return sprintf(
            '<div class="department-badges-container">%s</div>',
            implode('', $badges)
        );
    }

    /**
     * Generate HTML for status badge
     */
    private function generateStatusBadge(string $status): string {
        $label = $status === 'active' ? __('Aktif', 'wp-customer') : __('Non-aktif', 'wp-customer');
        return sprintf(
            '<span class="status-badge status-%s">%s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    /**
     * Generate action buttons HTML
     */
    private function generateActionButtons($employee) {
        $current_user_id = get_current_user_id();

        // Get customer untuk validasi
        $customer = $this->customerModel->find($employee->customer_id);
        if (!$customer) return '';

        $actions = '<div class="wpapp-actions">';

        // View Button
        if ($this->validator->canViewEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button view-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $employee->id,
                __('Lihat', 'wp-customer')
            );
        }

        // Edit Button
        if ($this->validator->canEditEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $employee->id,
                __('Edit', 'wp-customer')
            );
        }

        // Delete Button
        if ($this->validator->canDeleteEmployee($employee, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button delete-employee" data-id="%d" title="%s">
                    <i class="dashicons dashicons-trash"></i>
                </button>',
                $employee->id,
                __('Hapus', 'wp-customer')
            );
        }

        // Status Toggle Button
        if ($this->validator->canEditEmployee($employee, $customer)) {
            $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
            $statusTitle = $employee->status === 'active' ?
                __('Nonaktifkan', 'wp-customer') :
                __('Aktifkan', 'wp-customer');
            $statusIcon = $employee->status === 'active' ? 'remove' : 'yes';

            $actions .= sprintf(
                '<button type="button" class="button toggle-status" data-id="%d" data-status="%s" title="%s">
                    <i class="dashicons dashicons-%s"></i>
                </button>',
                $employee->id,
                $newStatus,
                $statusTitle,
                $statusIcon
            );
        }

        $actions .= '</div>'; // Close wpapp-actions wrapper
        return $actions;
    }

    // ========================================
    // MODAL AUTO-WIRE HANDLERS
    // ========================================

    /**
     * Handle save employee from modal (auto-wire)
     *
     * Wrapper method that delegates to parent update() method
     * Used by wp-datatable auto-wire modal system
     */
    public function handle_save_employee() {
        try {
            // Verify wpdt_nonce (from auto-wire system)
            $nonce = $_POST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                throw new \Exception(__('Security check failed', 'wp-customer'));
            }

            $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'create';

            if ($mode === 'edit') {
                // Get ID from POST
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if (!$id) {
                    throw new \Exception('Employee ID is required');
                }

                // Prepare and validate data
                $data = $this->prepareUpdateData($id);

                // Update via model
                if (!$this->model->update($id, $data)) {
                    throw new \Exception('Failed to update employee');
                }

                // Clear cache
                $this->cache->invalidateEmployeeCache($id);

                // Get updated employee
                $employee = $this->model->find($id);

                // Send success response
                wp_send_json_success([
                    'message' => __('Data karyawan berhasil diperbarui', 'wp-customer'),
                    'employee' => $employee
                ]);
            } else {
                // Create mode
                $data = $this->prepareCreateData();

                $employee_id = $this->model->create($data);
                if (!$employee_id) {
                    // Rollback user creation if employee creation failed
                    if (!empty($data['user_id'])) {
                        wp_delete_user($data['user_id']);
                    }
                    throw new \Exception('Gagal menambah karyawan');
                }

                // Send success response
                wp_send_json_success([
                    'message' => 'Karyawan berhasil ditambahkan',
                    'employee' => $this->model->find($employee_id)
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle delete employee from modal (auto-wire)
     *
     * Wrapper method that delegates to parent delete() method
     * Used by wp-datatable auto-wire modal system
     */
    public function handle_delete_employee() {
        try {
            // Verify wpdt_nonce (from auto-wire system)
            $nonce = $_POST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
                throw new \Exception(__('Security check failed', 'wp-customer'));
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Get employee and customer for permission check
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $customer = $this->customerModel->find($employee->customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Check delete permission
            if (!$this->validator->canDeleteEmployee($employee, $customer)) {
                throw new \Exception('Anda tidak memiliki izin untuk menghapus karyawan ini.');
            }

            // Validate delete
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Proceed with deletion
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete employee');
            }

            wp_send_json_success([
                'message' => __('Karyawan berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
