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
        add_action('wp_ajax_get_current_customer_id', [$this, 'getCurrentCustomerId']);

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

    $this->debug_log("Checking access for customer $customer_id by user $current_user_id");

    // Get customer entity from cache or model
    $customer = null;
    if ($customer_id > 0) {
        $customer = $this->cache->getCustomer($customer_id);
        if (!$customer) {
            $customer = $this->model->find($customer_id);
        }
    }

    // Initialize access array with customer entity
    $access = [
        'has_access' => false,
        'access_type' => null,
        'customer' => $customer
    ];

    // 1. Admin Check
    if (current_user_can('edit_all_customers')) {
        $access['has_access'] = true;
        $access['access_type'] = 'admin';
        return $access;
    }

    // 2. Owner Check
    $owner_query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers 
         WHERE " . ($customer_id > 0 ? "id = %d AND " : "") . "user_id = %d",
        ...($customer_id > 0 ? [$customer_id, $current_user_id] : [$current_user_id])
    );
    
    $is_owner = (int)$wpdb->get_var($owner_query) > 0;
    
    if ($is_owner && current_user_can('view_own_customer')) {
        $access['has_access'] = true;
        $access['access_type'] = 'owner';
        return $access;
    }

    // 3. Employee Check
    $employee_query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees 
         WHERE " . ($customer_id > 0 ? "customer_id = %d AND " : "") . "user_id = %d 
         AND status = 'active'",
        ...($customer_id > 0 ? [$customer_id, $current_user_id] : [$current_user_id])
    );
    
    $is_employee = (int)$wpdb->get_var($employee_query) > 0;
    
    if ($is_employee && current_user_can('view_own_customer')) {
        $access['has_access'] = true;
        $access['access_type'] = 'employee';
        return $access;
    }

    $this->debug_log('Checking customer' . print_r($customer), true);
    $this->debug_log('Checking access customer' . print_r($access), true);
    return $access;
}    
    public function getCheckCustomerAccess($customer_id) {
        $access = $this->checkCustomerAccess($customer_id);
        error_log('getCheckCustomerAccess result: ' . print_r($access, true));
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

    /**
     * Generate action buttons untuk DataTable row
     * 
     * @param object $customer Data customer dari row
     * @return string HTML button actions
     */
    private function generateActionButtons($customer) {
        $actions = '';
        
        // Debug logging
        $this->debug_log("==== Generating Action Buttons for Customer ID: {$customer->id} ====");
        
        // Dapatkan relasi user dengan customer ini
        $relation = $this->validator->getUserRelation($customer->id);
        
        // Log relasi untuk debugging
        $this->debug_log("User Relation:", $relation);

        // View Button
        if ($this->validator->canView($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button view-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                (int)$customer->id,
                __('Lihat', 'wp-customer')
            );
        }

        // Edit Button
        if ($this->validator->canUpdate($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                (int)$customer->id,
                __('Edit', 'wp-customer')
            );
        }

        // Delete Button
        if ($this->validator->canDelete($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button delete-customer" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                (int)$customer->id,
                __('Hapus', 'wp-customer')
            );
        }

        // Log final output untuk debugging
        $this->debug_log("Generated buttons:", [
            'customer_id' => $customer->id,
            'html' => $actions
        ]);

        return $actions;
    }

    /**
     * Handle customer creation request
     * Endpoint: wp_ajax_create_customer
     */
    public function store() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // 1. Validasi permission terlebih dahulu
            $permission_errors = $this->validator->validatePermission('create');
            if (!empty($permission_errors)) {
                wp_send_json_error([
                    'message' => __('Insufficient permissions', 'wp-customer')
                ]);
                return;
            }

            $current_user_id = get_current_user_id();
            
            // Debug POST data - hanya log data yang kita perlukan
            $debug_post = [
                'name' => $_POST['name'] ?? 'not set',
                'user_id' => $_POST['user_id'] ?? 'not set',
            ];
            $this->debug_log('Relevant POST data:');
            $this->debug_log($debug_post);
            
            // 2. Siapkan data dasar
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'created_by' => $current_user_id
            ];

            // 3. Handle user_id dengan beberapa skenario
            if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
                // Kasus: Admin membuat customer untuk user lain
                $data['user_id'] = absint($_POST['user_id']);
            } else if (isset($_POST['is_registration']) && $_POST['is_registration']) {
                // Kasus: User melakukan registrasi sendiri
                $data['user_id'] = $current_user_id;
                $data['created_by'] = $current_user_id;
            } else {
                // Default: User yang create menjadi owner
                $data['user_id'] = $current_user_id;
            }

            // 4. Tambahkan data opsional
            if (!empty($_POST['npwp'])) {
                $data['npwp'] = sanitize_text_field($_POST['npwp']);
            }
            if (!empty($_POST['nib'])) {
                $data['nib'] = sanitize_text_field($_POST['nib']);
            }

            // 5. Validasi form data
            $form_errors = $this->validator->validateForm($data);
            if (!empty($form_errors)) {
                wp_send_json_error([
                    'message' => implode(', ', $form_errors),
                    'errors' => $form_errors
                ]);
                return;
            }

            // Debug final data
            $this->debug_log('Data to be saved:');
            $this->debug_log($data);

            // 6. Buat customer baru
            $id = $this->model->create($data);
            if (!$id) {
                throw new \Exception(__('Failed to create customer', 'wp-customer'));
            }

            // 7. Get fresh data untuk response
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception(__('Failed to retrieve created customer', 'wp-customer'));
            }

            // 8. Clear cache yang relevan
            $this->cache->invalidateCustomerListCache();
            $this->cache->invalidateCustomerStatsCache();
            if ($data['user_id']) {
                $this->cache->invalidateUserCustomersCache($data['user_id']);
            }

            // 9. Return success response
            wp_send_json_success([
                'id' => $id,
                'customer' => $customer,
                'branch_count' => 0,
                'message' => __('Customer created successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            $this->debug_log('Create customer error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat menambah customer',
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * Handle customer update request
     * Endpoint: wp_ajax_update_customer
     */
    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Debug log input data
            $this->debug_log('Update request data:');
            $this->debug_log($_POST);

            // 1. Validasi permission terlebih dahulu
            $permission_errors = $this->validator->validatePermission('update', $id);
            if (!empty($permission_errors)) {
                wp_send_json_error([
                    'message' => reset($permission_errors)
                ]);
                return;
            }

            // 2. Siapkan data untuk validasi
            $data = [
                'name' => sanitize_text_field($_POST['name'])
            ];

            // Handle user_id jika ada
            if (isset($_POST['user_id'])) {
                $data['user_id'] = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
            }

            // 3. Validasi form data
            $form_errors = $this->validator->validateForm($data, $id);
            if (!empty($form_errors)) {
                wp_send_json_error([
                    'message' => implode(', ', $form_errors)
                ]);
                return;
            }

            // 4. Lakukan update jika semua validasi sukses
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update customer');
            }

            // 5. Invalidate cache
            $this->cache->invalidateCustomerCache($id);
            $this->cache->invalidateCustomerListCache();
            $this->cache->invalidateCustomerStatsCache();

            // 6. Get fresh data untuk response
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Failed to retrieve updated customer');
            }

            // Debug response data
            $this->debug_log('Update response data:');
            $this->debug_log([
                'customer' => $customer,
                'branch_count' => $this->model->getBranchCount($id)
            ]);

            // 7. Return success response
            wp_send_json_success([
                'message' => __('Customer updated successfully', 'wp-customer'),
                'data' => [
                    'customer' => $customer,
                    'branch_count' => $this->model->getBranchCount($id)
                ]
            ]);

        } catch (\Exception $e) {
            $this->debug_log('Update error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
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

    /**
     * Get customer ID associated with current logged in user.
     * Checks both owner and employee relationships to determine the active customer.
     * Used internally by other methods requiring customer context.
     * 
     * @access private
     * @since 1.0.0
     * @return int Customer ID if found, 0 otherwise
     * 
     * @example
     * // Inside another controller method:
     * $customer_id = $this->getCurrentUserCustomerId();
     * if ($customer_id > 0) {
     *     // Process for specific customer
     * }
     */
    private function getCurrentUserCustomerId() {
        global $wpdb;
        $current_user_id = get_current_user_id();

        // Check if user is owner of any customer
        $customer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_customers 
             WHERE user_id = %d 
             LIMIT 1",
            $current_user_id
        ));

        if ($customer_id) {
            return (int)$customer_id;
        }

        // If not owner, check if user is employee
        $customer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id 
             FROM {$wpdb->prefix}app_customer_employees 
             WHERE user_id = %d 
             AND status = 'active' 
             LIMIT 1",
            $current_user_id
        ));

        return $customer_id ? (int)$customer_id : 0;
    }

    /**
     * AJAX endpoint to provide current user's customer ID to frontend.
     * Returns the customer ID based on user's relationship (owner/employee).
     * Used by JavaScript to determine active customer context.
     * 
     * @access public
     * @since 1.0.0
     * @uses WP_Customer_Controller::getCurrentUserCustomerId()
     * @uses check_ajax_referer() For security validation
     * @uses wp_send_json_success() To return customer ID
     * @uses wp_send_json_error() To return error message
     * 
     * @fires wp_ajax_get_current_customer_id
     * 
     * @example
     * // From JavaScript:
     * $.ajax({
     *     url: wpCustomerData.ajaxUrl,
     *     data: {
     *         action: 'get_current_customer_id',
     *         nonce: wpCustomerData.nonce
     *     },
     *     success: function(response) {
     *         const customerId = response.data.customer_id;
     *     }
     * });
     */
    public function getCurrentCustomerId() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $customer_id = $this->getCurrentUserCustomerId();
            
            $this->debug_log("Got customer ID for current user: " . $customer_id);
            
            wp_send_json_success([
                'customer_id' => $customer_id
            ]);

        } catch (\Exception $e) {
            $this->debug_log("Error getting customer ID: " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
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

    public function getCustomerData($id) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        
        $this->debug_log("Getting customer data for ID: " . $id);

        try {
            // Jika id = 0, coba dapatkan customer berdasarkan user yang login
            if ($id === 0) {
                // Cek apakah user adalah owner dari customer
                $customer = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}app_customers 
                     WHERE user_id = %d 
                     LIMIT 1",
                    $current_user_id
                ));

                if ($customer) {
                    $this->debug_log("Found customer data for user: " . $current_user_id);
                    // Tambahkan data tambahan
                    $customer->branch_count = $this->model->getBranchCount($customer->id);
                    return $customer;
                }

                // Jika bukan owner, cek apakah user adalah employee
                $employee_customer = $wpdb->get_row($wpdb->prepare(
                    "SELECT c.* 
                     FROM {$wpdb->prefix}app_customers c
                     JOIN {$wpdb->prefix}app_customer_employees e ON e.customer_id = c.id
                     WHERE e.user_id = %d AND e.status = 'active'
                     LIMIT 1",
                    $current_user_id
                ));

                if ($employee_customer) {
                    $this->debug_log("Found customer data through employee relationship");
                    $employee_customer->branch_count = $this->model->getBranchCount($employee_customer->id);
                    return $employee_customer;
                }
            } else {
                // Jika id spesifik diberikan
                // Coba ambil dari cache dulu
                $customer = $this->cache->getCustomer($id);
                
                // Jika tidak ada di cache, ambil dari database
                if (!$customer) {
                    $customer = $this->model->find($id);
                    if ($customer) {
                        $customer->branch_count = $this->model->getBranchCount($id);
                        return $customer;
                    }
                }
            }

            $this->debug_log("No customer data found");
            return null;

        } catch (\Exception $e) {
            $this->debug_log("Error getting customer data: " . $e->getMessage());
            return null;
        }
    }

    public function renderMainPage() {
        global $wpdb;
        $current_user_id = get_current_user_id();

        $this->debug_log('--- Debug Controller renderMainPage ---');
        $this->debug_log('User ID: ' . $current_user_id);

        // Basic Access Check
        if (!current_user_can('view_customer_list')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        // Get customer_id dari URL atau hash
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($customer_id === 0 && isset($_SERVER['REQUEST_URI'])) {
            $hash = parse_url($_SERVER['REQUEST_URI'], PHP_URL_FRAGMENT);
            if ($hash && is_numeric($hash)) {
                $customer_id = (int)$hash;
            }
        }

        $this->debug_log('Customer ID from params: ' . $customer_id);

        // Setup template data
        $template_data = [
            'customer' => $this->getCustomerData($customer_id),
            'access' => $this->getCheckCustomerAccess($customer_id),
            'controller' => $this,
            'branch_model' => new \WPCustomer\Models\Branch\BranchModel(),
            'employee_model' => new \WPCustomer\Models\Employee\CustomerEmployeeModel(),
            'branches' => [], // Default empty array
            'employees' => []  // Default empty array
        ];
        
        // Debug log template data
        $this->debug_log('Template data:');
        $this->debug_log($template_data);

        // Render appropriate template
        if ($template_data['access']['has_access']) {
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
        } else {
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-no-access.php';
        }
    }

}
