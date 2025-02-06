<?php
/**
 * Customer Employee Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Employee/CustomerEmployeeController.php
 *
 * Description: Controller untuk mengelola data karyawan customer.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk panel kanan.
 *              Menyediakan endpoints untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added CRUD operations
 * - Added DataTable integration
 * - Added permission handling
 */

namespace WPCustomer\Controllers\Employee;

use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Validators\Employee\CustomerEmployeeValidator;
use WPCustomer\Cache\CustomerCacheManager;

class CustomerEmployeeController {
    private CustomerEmployeeModel $model;
    private CustomerEmployeeValidator $validator;
    private CustomerCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/employee.log';

    public function __construct() {
        $this->model = new CustomerEmployeeModel();
        $this->validator = new CustomerEmployeeValidator();
        $this->cache = new CustomerCacheManager();

        // Initialize log file in plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX endpoints
        add_action('wp_ajax_handle_employee_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_employee', [$this, 'show']);
        add_action('wp_ajax_create_employee', [$this, 'store']);
        add_action('wp_ajax_update_employee', [$this, 'update']);
        add_action('wp_ajax_delete_employee', [$this, 'delete']);
        add_action('wp_ajax_change_employee_status', [$this, 'changeStatus']);
        
    }

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
     * Handle DataTable AJAX request
     */
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
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

            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Map column index to column name
            $columns = ['name', 'position', 'departments', 'email', 'branch_name', 'status', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

            if ($orderBy === 'actions' || $orderBy === 'departments') {
                $orderBy = 'name'; // Default sort if actions or departments column
            }

            try {
                $result = $this->model->getDataTableData($customer_id, $start, $length, $search, $orderBy, $orderDir);

                $data = [];
                foreach ($result['data'] as $employee) {
                    // Generate departments HTML
                    $departments_html = $this->generateDepartmentsBadges([
                        'finance' => (bool)$employee->finance,
                        'operation' => (bool)$employee->operation,
                        'legal' => (bool)$employee->legal,
                        'purchase' => (bool)$employee->purchase
                    ]);

                    $data[] = [
                        'id' => $employee->id,
                        'name' => esc_html($employee->name),
                        'position' => esc_html($employee->position),
                        'department' => $departments_html,
                        'email' => esc_html($employee->email),
                        'branch_name' => esc_html($employee->branch_name),
                        'status' => $employee->status,
                        'actions' => $this->generateActionButtons($employee)
                    ];
                }

                wp_send_json([
                    'draw' => $draw,
                    'recordsTotal' => $result['total'],
                    'recordsFiltered' => $result['filtered'],
                    'data' => $data
                ]);

            } catch (\Exception $modelException) {
                throw new \Exception('Database error: ' . $modelException->getMessage());
            }

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 400);
        }
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
            $actions = '';
            $current_user_id = get_current_user_id();

            // Debug header untuk karyawan ini
            $this->debug_log("==== Generating Action Buttons for Employee ID: {$employee->id} ====");
            
            // 1. Dapatkan data customer untuk cek ownership
            global $wpdb;
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}app_customers WHERE id = %d",
                $employee->customer_id
            ));

            // Log employee data
            $this->debug_log([
                'employee_id' => (int)$employee->id,
                'customer_id' => (int)$employee->customer_id,
                'customer_owner_id' => $customer ? (int)$customer->user_id : 'not found',
                'current_user_id' => (int)$current_user_id,
                'employee_created_by' => (int)$employee->created_by
            ]);

            // 2. Cek apakah user adalah owner
            $is_owner = $customer && ((int)$customer->user_id === (int)$current_user_id);
            
            // 3. Cek apakah user adalah staff
            $is_staff = (bool)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
                 WHERE customer_id = %d AND user_id = %d",
                $employee->customer_id, 
                $current_user_id
            ));

            // Log permission context
            $this->debug_log("Permission Context:");
            $this->debug_log([
                'is_owner' => $is_owner,
                'is_staff' => $is_staff,
                'is_creator' => ((int)$employee->created_by === (int)$current_user_id),
                'has_view_detail' => current_user_can('view_employee_detail'),
                'has_edit_all' => current_user_can('edit_all_employees'),
                'has_edit_own' => current_user_can('edit_own_employee'),
                'has_delete' => current_user_can('delete_employee')
            ]);

            // 4. View Button Logic
            // - Owner selalu bisa lihat
            // - Staff bisa lihat semua dalam customernya
            // - Admin dengan view_employee_detail bisa lihat semua
            if ($is_owner || $is_staff || current_user_can('view_employee_detail')) {
                $actions .= sprintf(
                    '<button type="button" class="button view-employee" data-id="%d" title="%s">
                        <i class="dashicons dashicons-visibility"></i>
                    </button> ',
                    $employee->id,
                    __('Lihat', 'wp-customer')
                );
                $this->debug_log("Added View Button");
            }

            // 5. Edit Button Logic
            // - Owner bisa edit semua karyawan dalam customernya
            // - Staff hanya bisa edit karyawan yang dia tambahkan
            // - Admin dengan edit_all_employees bisa edit semua
            if (current_user_can('edit_all_employees') || 
                $is_owner || 
                (current_user_can('edit_own_employee') && (int)$employee->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button edit-employee" data-id="%d" title="%s">
                        <i class="dashicons dashicons-edit"></i>
                    </button> ',
                    $employee->id,
                    __('Edit', 'wp-customer')
                );
                $this->debug_log("Added Edit Button");
            }

            // 6. Delete Button Logic
            // - Owner bisa hapus semua karyawan dalam customernya
            // - Staff hanya bisa hapus karyawan yang dia tambahkan
            // - Admin dengan delete_employee bisa hapus semua
            if (current_user_can('delete_employee') || 
                $is_owner || 
                (current_user_can('delete_employee') && (int)$employee->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button delete-employee" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $employee->id,
                    __('Hapus', 'wp-customer')
                );
                $this->debug_log("Added Delete Button");
            }

            // 7. Status Toggle Button (Aktif/Nonaktif)
            // - Owner bisa mengubah status semua karyawan
            // - Staff hanya bisa mengubah status karyawan yang dia tambahkan
            // - Admin dengan edit_all_employees bisa mengubah semua
            if (current_user_can('edit_all_employees') || 
                $is_owner || 
                (current_user_can('edit_own_employee') && (int)$employee->created_by === (int)$current_user_id)) {
                
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
                $this->debug_log("Added Status Toggle Button");
            }

            // Log final buttons
            $this->debug_log("Final action buttons HTML: " . $actions);
            $this->debug_log("==== End Action Buttons Generation ====\n");

            return $actions;
        }

    /**
     * Show employee details
     */
    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Validate view permission
            $errors = $this->validator->validateView($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            wp_send_json_success($employee);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store() {
        try {
            error_log('Received POST data: ' . print_r($_POST, true));

            check_ajax_referer('wp_customer_nonce', 'nonce');

            // Basic data sanitization
            $data = [
                'customer_id' => isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0,
                'branch_id' => isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : 0,
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'position' => sanitize_text_field($_POST['position'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                // Boolean department fields
                'finance' => isset($_POST['finance']) ? (bool) $_POST['finance'] : false,
                'operation' => isset($_POST['operation']) ? (bool) $_POST['operation'] : false,
                'legal' => isset($_POST['legal']) ? (bool) $_POST['legal'] : false,
                'purchase' => isset($_POST['purchase']) ? (bool) $_POST['purchase'] : false
            ];

            error_log('Sanitized data: ' . print_r($data, true));

            // Validate input
            $errors = $this->validator->validateCreate($data);
            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors));
            }

            // Create employee
            $id = $this->model->create($data);
            if (!$id) {
                throw new \Exception('Failed to create employee');
            }

            // Get fresh data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Failed to retrieve created employee');
            }

            wp_send_json_success([
                'message' => __('Karyawan berhasil ditambahkan', 'wp-customer'),
                'employee' => $employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Basic data sanitization
            $data = [
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'position' => sanitize_text_field($_POST['position'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                'branch_id' => isset($_POST['branch_id']) ? (int) $_POST['branch_id'] : 0,
                // Boolean department fields
                'finance' => isset($_POST['finance']) ? (bool) $_POST['finance'] : false,
                'operation' => isset($_POST['operation']) ? (bool) $_POST['operation'] : false,
                'legal' => isset($_POST['legal']) ? (bool) $_POST['legal'] : false,
                'purchase' => isset($_POST['purchase']) ? (bool) $_POST['purchase'] : false
            ];

            // Validate input
            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors));
            }

            // Update employee
            if (!$this->model->update($id, $data)) {
                throw new \Exception('Failed to update employee');
            }

            // Get fresh data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Failed to retrieve updated employee');
            }

            wp_send_json_success([
                'message' => __('Data karyawan berhasil diperbarui', 'wp-customer'),
                'employee' => $employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete employee
     */
    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

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

    /**
     * Change employee status
     */
    public function changeStatus() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid employee ID');
            }

            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status');
            }

            // Validate edit permission
            if (!current_user_can('edit_employee')) {
                throw new \Exception(__('Anda tidak memiliki izin untuk mengubah status karyawan.', 'wp-customer'));
            }

            // Get employee data
            $employee = $this->model->find($id);
            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            // Update status
            if (!$this->model->changeStatus($id, $status)) {
                throw new \Exception('Failed to update employee status');
            }

            // Get fresh data
            $employee = $this->model->find($id);

            wp_send_json_success([
                'message' => __('Status karyawan berhasil diperbarui', 'wp-customer'),
                'employee' => $employee
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
