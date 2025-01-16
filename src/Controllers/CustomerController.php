<?php
/**
* Customer Controller Class
*
* @package     WP_Customer
* @subpackage  Controllers
* @version     1.0.0
* @author      arisciwek
*
* Path: /wp-customer/src/Controllers/CustomerController.php
*
* Description: Controller untuk mengelola data customer.
*              Menangani operasi CRUD dengan integrasi cache.
*              Includes validasi input, permission checks,
*              dan response formatting untuk panel kanan.
*              Menyediakan endpoints untuk DataTables server-side.
*
* Changelog:
* 1.0.1 - 2024-12-08
* - Added view_own_customer permission check in show method
* - Enhanced permission validation
* - Improved error handling for permission checks
*
* Changelog:
* 1.0.0 - 2024-12-03 14:30:00
* - Refactor CRUD responses untuk panel kanan
* - Added cache integration di semua endpoints
* - Added konsisten response format
* - Added validasi dan permission di semua endpoints
* - Improved error handling dan feedback
*/

namespace WPCustomer\Controllers;

use WPCustomer\Models\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Cache\CacheManager;

class CustomerController {
    private $error_messages;
    private CustomerModel $model;
    private CustomerValidator $validator;
    private CacheManager $cache;
    private BranchModel $branchModel;  // Tambahkan ini

    private string $log_file;

    private function logPermissionCheck($action, $user_id, $customer_id, $branch_id = null, $result) {
        $this->debug_log(sprintf(
            'Permission check for %s - User: %d, Customer: %d, Branch: %s, Result: %s',
            $action,
            $user_id,
            $customer_id,
            $branch_id ?? 'none',  // Gunakan null coalescing untuk handle null branch_id
            $result ? 'granted' : 'denied'
        ));
    }

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/customer.log';

    public function __construct() {
        $this->model = new CustomerModel();
        $this->branchModel = new BranchModel();  // Inisialisasi di constructor
        $this->validator = new CustomerValidator();
        $this->cache = new CacheManager();

        // Inisialisasi error messages
        $this->error_messages = [
            'insufficient_permissions' => __('Anda tidak memiliki izin untuk melakukan operasi ini', 'wp-customer'),
            'view_denied' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
            'edit_denied' => __('Anda tidak memiliki izin untuk mengubah data ini', 'wp-customer'),
            'delete_denied' => __('Anda tidak memiliki izin untuk menghapus data ini', 'wp-customer'),
        ];


        // Inisialisasi log file di dalam direktori plugin
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Pastikan direktori logs ada
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_customer_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_customer_datatable', [$this, 'handleDataTableRequest']);

        // Register endpoint untuk update
        add_action('wp_ajax_update_customer', [$this, 'update']);

        // Register endpoint lain yang diperlukan
        add_action('wp_ajax_get_customer', [$this, 'show']);
        add_action('wp_ajax_create_customer', [$this, 'store']);
        add_action('wp_ajax_delete_customer', [$this, 'delete']);
        add_action('wp_ajax_validate_customer_access', [$this, 'validateCustomerAccess']);

    }


    /**
     * Validate customer access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateCustomerAccess() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            // Gunakan method private untuk validasi internal
            $access = $this->checkCustomerAccess($customer_id);
            
            if (!$access['has_access']) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki akses ke customer ini', 'wp-customer'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            wp_send_json_success([
                'message' => 'Akses diberikan',
                'customer_id' => $customer_id,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'error'
            ]);
        }
    }


    /**
     * Internal method untuk validasi akses customer
     * @param int $customer_id
     * @return array
     */
    private function checkCustomerAccess($customer_id) {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Debug log
        $this->debug_log("Checking access for customer $customer_id by user $current_user_id");

        // 1. Admin Check
        if (current_user_can('edit_all_customers')) {
            $this->debug_log("User has admin access");
            return [
                'has_access' => true,
                'access_type' => 'admin'
            ];
        }

// 2. Owner Check  
$is_owner = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers 
     WHERE id = %d AND user_id = %d",
    $customer_id, $current_user_id
));
$this->debug_log("Owner check result: " . ($is_owner ? 'true' : 'false'));

if ($is_owner && current_user_can('view_own_customer')) {
    $this->debug_log("User is owner");
    
    // Branch permission check untuk owner
    $branch_access = [
        'view_branch' => current_user_can('view_branch_list') || current_user_can('view_own_branch'),
        'add_branch' => current_user_can('add_branch'),
        'edit_branch' => current_user_can('edit_own_branch'),
        'delete_branch' => current_user_can('delete_branch')
    ];
    
    $this->debug_log("Branch permissions for owner:");
    $this->debug_log($branch_access);

    return [
        'has_access' => true,
        'access_type' => 'owner',
        'branch_access' => $branch_access
    ];
}
        // 3. Employee Check
        $is_employee = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
             WHERE customer_id = %d AND user_id = %d AND status = 'active'",
            $customer_id, $current_user_id
        ));

        $this->debug_log("Employee check result: " . ($is_employee ? 'true' : 'false'));

        if ($is_employee && current_user_can('view_own_customer')) {
            return [
                'has_access' => true,
                'access_type' => 'employee'
            ];
        }

        $this->debug_log("No access found");
        return [
            'has_access' => false,
            'access_type' => null
        ];
    }
    
    public function getCheckCustomerAccess($customer_id) {
        $access = $this->checkCustomerAccess($customer_id);
        
        // Add branch-specific permissions
        if ($access['has_access']) {
            $access['branch_access'] = [
                'view_branch' => current_user_can('view_branch_list') || current_user_can('view_own_branch'),
                'add_branch' => current_user_can('add_branch'),
                'edit_branch' => current_user_can('edit_own_branch'),
                'delete_branch' => current_user_can('delete_branch')
            ];
        }
        
    $result = $this->checkCustomerAccess($customer_id);
    error_log('getCheckCustomerAccess result: ' . print_r($result, true));
    return $result;
                    
        return $access;
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Get WordPress uploads directory information
        $upload_dir = wp_upload_dir();
        $customer_base_dir = $upload_dir['basedir'] . '/wp-customer';
        $customer_log_dir = $customer_base_dir . '/logs';
        
        // Update log file path with monthly rotation format
        $this->log_file = $customer_log_dir . '/customer-' . date('Y-m') . '.log';

        // Create base wp-customer directory if it doesn't exist
        if (!file_exists($customer_base_dir)) {
            if (!wp_mkdir_p($customer_base_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create base directory in uploads: ' . $customer_base_dir);
                return;
            }
            
            // Add .htaccess to base directory
            $base_htaccess_content = "# Protect Directory\n";
            $base_htaccess_content .= "<FilesMatch \"^.*$\">\n";
            $base_htaccess_content .= "Order Deny,Allow\n";
            $base_htaccess_content .= "Deny from all\n";
            $base_htaccess_content .= "</FilesMatch>\n";
            $base_htaccess_content .= "\n";
            $base_htaccess_content .= "# Allow specific file types if needed\n";
            $base_htaccess_content .= "<FilesMatch \"\.(jpg|jpeg|png|gif|css|js)$\">\n";
            $base_htaccess_content .= "Order Allow,Deny\n";
            $base_htaccess_content .= "Allow from all\n";
            $base_htaccess_content .= "</FilesMatch>";
            
            @file_put_contents($customer_base_dir . '/.htaccess', $base_htaccess_content);
            @chmod($customer_base_dir, 0755);
        }

        // Create logs directory if it doesn't exist
        if (!file_exists($customer_log_dir)) {
            if (!wp_mkdir_p($customer_log_dir)) {
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create log directory in uploads: ' . $customer_log_dir);
                return;
            }

            // Add .htaccess to logs directory with strict rules
            $logs_htaccess_content = "# Deny access to all files\n";
            $logs_htaccess_content .= "Order deny,allow\n";
            $logs_htaccess_content .= "Deny from all\n\n";
            $logs_htaccess_content .= "# Deny access to log files specifically\n";
            $logs_htaccess_content .= "<Files ~ \"\.log$\">\n";
            $logs_htaccess_content .= "Order allow,deny\n";
            $logs_htaccess_content .= "Deny from all\n";
            $logs_htaccess_content .= "</Files>\n\n";
            $logs_htaccess_content .= "# Extra protection\n";
            $logs_htaccess_content .= "<IfModule mod_php.c>\n";
            $logs_htaccess_content .= "php_flag engine off\n";
            $logs_htaccess_content .= "</IfModule>";
            
            @file_put_contents($customer_log_dir . '/.htaccess', $logs_htaccess_content);
            @chmod($customer_log_dir, 0755);
        }

        // Create log file if it doesn't exist
        if (!file_exists($this->log_file)) {
            if (@touch($this->log_file)) {
                chmod($this->log_file, 0644);
            } else {
                error_log('Failed to create log file: ' . $this->log_file);
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                return;
            }
        }

        // Double check writability
        if (!is_writable($this->log_file)) {
            error_log('Log file not writable: ' . $this->log_file);
            $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
        }
    }

    /**
     * Log debug messages ke file
     *
     * @param mixed $message Pesan yang akan dilog
     * @return void
     */
    private function debug_log($message): void {
        // Hanya jalankan jika WP_DEBUG aktif
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');

        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $log_message = "[{$timestamp}] {$message}\n";

        // Gunakan error_log bawaan WordPress dengan custom log file
        error_log($log_message, 3, $this->log_file);
    }

    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }
        
            $hasPermission = current_user_can('view_customer_list');
            $this->logPermissionCheck(
                'view_customer_list',
                get_current_user_id(),
                0,  // No specific customer
                null, // No specific branch
                $hasPermission
            );

            if (!$hasPermission) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            // Get and validate parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Map column index to column name
            $columns = ['code', 'name', 'owner_name', 'branch_count', 'actions']; // tambah owner_name

            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'code';

            if ($orderBy === 'actions') {
                $orderBy = 'code'; // Default sort jika kolom actions
            }

            try {
                $result = $this->model->getDataTableData($start, $length, $search, $orderBy, $orderDir);

                if (!$result) {
                    throw new \Exception('No data returned from model');
                }

                $data = [];
                foreach ($result['data'] as $customer) {
                    $owner_name = '-';
                    if (!empty($customer->user_id)) {
                        $user = get_userdata($customer->user_id);
                        $owner_name = $user ? $user->display_name : '-';
                    }

                    $data[] = [
                        'id' => $customer->id,
                        'code' => esc_html($customer->code),
                        'name' => esc_html($customer->name),
                        'owner_name' => esc_html($owner_name), // Tambahkan ini
                        'branch_count' => intval($customer->branch_count),
                        'actions' => $this->generateActionButtons($customer)
                    ];
                }

                $response = [
                    'draw' => $draw,
                    'recordsTotal' => $result['total'],
                    'recordsFiltered' => $result['filtered'],
                    'data' => $data,
                ];

                wp_send_json($response);

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

    private function generateActionButtons($customer) {
        $actions = '';
        $current_user_id = (int)get_current_user_id();
        
        // Debug logging
        $this->debug_log("==== Generating Action Buttons for Customer ID: {$customer->id} ====");
        
        // Log customer data
        $this->debug_log("Customer Data:");
        $this->debug_log([
            'id' => (int)$customer->id,
            'user_id' => (int)$customer->user_id,
            'current_user' => $current_user_id
        ]);
        
        // View Button Check

        $can_view = current_user_can('view_customer_list') || 
                    (current_user_can('view_own_customer') && (int)$customer->user_id === $current_user_id) ||
                    ($is_employee > 0 && current_user_can('view_customer_list'));

        $this->debug_log("View Button Conditions:");
        $access = $this->checkCustomerAccess($customer->id);
        $can_view = $access['has_access'];

        $this->debug_log("View Button Conditions:");
        $this->debug_log([
            'has_view_list_permission' => current_user_can('view_customer_list'),
            'has_view_own_permission' => current_user_can('view_own_customer'),
            'is_owner' => ((int)$customer->user_id === $current_user_id),
            'is_employee' => $access['access_type'] === 'employee',
            'access_type' => $access['access_type'],
            'final_view_decision' => $can_view
        ]);

        if ($can_view) {
            $actions .= sprintf(
                '<button type="button" class="button view-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                (int)$customer->id,
                __('Lihat', 'wp-customer')
            );
        }
        
        // Edit Button Check - based on edit_own_customer
        $can_edit = (current_user_can('edit_own_customer') && 
                     (int)$customer->user_id === $current_user_id);
        
        $this->debug_log("Edit Button Conditions:");
        $this->debug_log([
            'has_edit_own_permission' => current_user_can('edit_own_customer'),
            'is_owner' => ((int)$customer->user_id === $current_user_id),
            'final_edit_decision' => $can_edit
        ]);
        
        if ($can_edit) {
            $actions .= sprintf(
                '<button type="button" class="button edit-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                (int)$customer->id,
                __('Edit', 'wp-customer')
            );
        }
        
        // Delete Button Check - false by default for customer role
        if (current_user_can('delete_customer')) {
            $actions .= sprintf(
                '<button type="button" class="button delete-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                (int)$customer->id,
                __('Hapus', 'wp-customer')
            );
        }
        
        $this->debug_log("Final Actions HTML: " . $actions);
        return $actions;
    }

    public function store() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('add_customer')) {
                wp_send_json_error([
                    'message' => __('Insufficient permissions', 'wp-customer')
                ]);
                return;
            }

            $current_user_id = get_current_user_id();
            
            // Debug POST data - hanya log data yang kita perlukan
            $debug_post = [
                'name' => $_POST['name'] ?? 'not set',
                'code' => $_POST['code'] ?? 'not set',
                'user_id' => $_POST['user_id'] ?? 'not set',
            ];
            $this->debug_log('Relevant POST data:');
            $this->debug_log($debug_post);
            
            // Basic data
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'code' => sanitize_text_field($_POST['code']),
                'created_by' => $current_user_id
            ];

            // Handle user_id
            if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
                $data['user_id'] = absint($_POST['user_id']);
            } else {
                $data['user_id'] = $current_user_id;
            }

            // Debug final data
            $this->debug_log('Data to be saved:');
            $this->debug_log($data);

            // Validate input
            $errors = $this->validator->validateCreate($data);
            if (!empty($errors)) {
                wp_send_json_error([
                    'message' => is_array($errors) ? implode(', ', $errors) : $errors,
                    'errors' => $errors
                ]);
                return;
            }

            // Get ID from creation
            $id = $this->model->create($data);
            if (!$id) {
                wp_send_json_error([
                    'message' => __('Failed to create customer', 'wp-customer')
                ]);
                return;
            }

            // Get fresh data for response
            $customer = $this->model->find($id);
            if (!$customer) {
                wp_send_json_error([
                    'message' => __('Failed to retrieve created customer', 'wp-customer')
                ]);
                return;
            }

            wp_send_json_success([
                'id' => $id,
                'customer' => $customer,
                'branch_count' => 0,
                'message' => __('Customer created successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat menambah customer',
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Get existing customer data
            $existing_customer = $this->model->find($id);
            if (!$existing_customer) {
                throw new \Exception('Customer not found');
            }

            // Check permissions
            if (!current_user_can('edit_all_customers') && 
                (!current_user_can('edit_own_customer') || $existing_customer->created_by !== get_current_user_id())) {
                wp_send_json_error([
                    'message' => __('You do not have permission to edit this customer', 'wp-customer')
                ]);
                return;
            }

            // Basic data
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'code' => sanitize_text_field($_POST['code'])
            ];

            $this->debug_log('POST: ' . print_r($_POST, true));

            // Handle user_id
            if (isset($_POST['user_id'])) {
                if (current_user_can('edit_all_customers')) {
                    $data['user_id'] = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
                    $this->debug_log('Setting user_id to: ' . print_r($data['user_id'], true));
                } else {
                    $this->debug_log('User lacks permission to change user_id');
                }
            }

            // If no edit_all_customers capability, user_id remains unchanged

            // Validate input
            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                wp_send_json_error(['message' => implode(', ', $errors)]);
                return;
            }

            // Update data
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update customer');
            }

            // Invalidate cache
            $this->cache->invalidateCustomerCache($id);

            // Get updated data
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Failed to retrieve updated customer');
            }

            wp_send_json_success([
                'message' => __('Customer updated successfully', 'wp-customer'),
                'data' => [
                    'customer' => $customer,
                    'branch_count' => $this->model->getBranchCount($id)
                ]
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Coba ambil dari cache dulu
            $customer = $this->cache->getCustomer($id);
            
            // Jika tidak ada di cache, ambil dari database
            if (!$customer) {
                $customer = $this->model->find($id);
                if (!$customer) {
                    throw new \Exception('Customer not found');
                }
            }

            // Gunakan checkCustomerAccess untuk konsistensi
            $access = $this->checkCustomerAccess($id);
            $this->logPermissionCheck(
                'view_customer_detail',
                get_current_user_id(), 
                $id,
                null,
                $access['has_access']
            );

            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            $customer->access_type = $access['access_type'];
            $customer->has_access = $access['has_access'];

            wp_send_json_success([
                'customer' => $customer,
                'branch_count' => $this->model->getBranchCount($id),
                'access_type' => $access['access_type'] // berguna untuk UI
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Add this check
            if (!current_user_can('delete_customer')) {
                throw new \Exception('Insufficient permissions');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Perform delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete customer');
            }

            // Clear cache
            $this->cache->invalidateCustomerCache($id);

            wp_send_json_success([
                'message' => __('Data Customer berhasil dihapus', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // Di CustomerController
    public function getStats() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // Get customer_id from query param
            $customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            error_log('Query param customer_id: ' . $customer_id);

            $stats = [
                'total_customers' => $this->model->getTotalCount(),
                'total_branches' => $this->branchModel->getTotalCount($customer_id)
            ];

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

        
    public function renderMainPage() {
        global $wpdb;
        $current_user_id = get_current_user_id();

        error_log('--- Debug MenuManager renderMainPage ---');
        error_log('User ID: ' . $current_user_id);

        // Dapatkan customer_id dari query parameter

        $customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        error_log('Query param customer_id: ' . $customer_id);

        // Cek relasi sebagai employee dengan customer spesifik
        $customer_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT customer_id 
             FROM {$wpdb->prefix}app_customer_employees 
             WHERE user_id = %d",
            $current_user_id
        ));
        
        error_log('Employee customer IDs: ' . print_r($customer_ids, true));


        // Cek akses ke plugin
        error_log('Checking basic plugin access:');
        error_log('Can view_customer_list: ' . (current_user_can('view_customer_list') ? 'yes' : 'no'));

        if (!current_user_can('view_customer_list')) {
            error_log('Basic access check failed - user cannot view customer list');
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        // Admin check
        error_log('Checking admin access:');
        error_log('Can edit_all_customers: ' . (current_user_can('edit_all_customers') ? 'yes' : 'no'));

        if (current_user_can('edit_all_customers')) {
            error_log('Admin access granted - showing full dashboard');
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
            return;
        }

        // Cek relasi sebagai owner
        $has_customer = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers WHERE user_id = %d",
            $current_user_id
        ));
        error_log('Checking owner relationship:');
        error_log('Has customer records: ' . ($has_customer > 0 ? 'yes' : 'no'));

        // Jika user adalah owner, berikan akses
        if ($has_customer > 0 && current_user_can('view_own_customer')) {
            error_log('Owner access granted - showing customer dashboard');
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
            return;
        }

        // Jika customer_id dari query parameter ada dalam daftar customer_ids employee
        if (!empty($customer_ids) && ($customer_id === 0 || in_array($customer_id, $customer_ids))) {
            error_log('Employee access granted for customer_id: ' . $customer_id);
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
            return;
        }

        error_log('All checks failed - showing no access template');
        error_log('--- End Debug MenuManager renderMainPage ---');
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-no-access.php';
    }

}
