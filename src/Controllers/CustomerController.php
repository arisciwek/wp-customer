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
use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Cache\CacheManager;

class CustomerController {
    private $error_messages;
    private CustomerModel $model;
    private CustomerValidator $validator;
    private CacheManager $cache;
    private BranchModel $branchModel;  // Tambahkan ini
    private CustomerEmployeeModel $customerEmployeeModel;  // Tambahkan ini

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
        $this->branchModel = new BranchModel();
        $this->customerEmployeeModel = new CustomerEmployeeModel();
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
        add_action('wp_ajax_get_customer_data_ajax', [$this, 'get_customer_data_ajax']);
        add_action('wp_ajax_get_tab_content', [$this, 'get_tab_content']);
    }

    public function get_customer_data_ajax() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            // Validate access
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('Access denied');
            }
            error_log('Access data: ' . print_r($access, true));

            // Get customer data
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }
            error_log('Customer data: ' . print_r($customer, true));

            // Get related data
            $branches = $this->branchModel->getByCustomer($id);
            $employees = $this->customerEmployeeModel->getByCustomer($id);

            // Prepare view data
            $data = [
                'customer' => $customer,
                'access' => $access,
                'branches' => $branches,
                'employees' => $employees
            ];
            error_log('Data for template: ' . print_r($data, true));

            // Render template
            ob_start();
            extract($data);
            require WP_CUSTOMER_PATH . 'src/Views/templates/customer-right-panel.php';
            $html = ob_get_clean();

        error_log('Generated AJAX HTML length: ' . strlen($html));
        error_log('First 501 characters of HTML:');
        error_log(substr($html, 0, 500));
        error_log('Last 501 characters of HTML:');
        error_log(substr($html, -500));
        error_log('=== End Debug Tab Content ===');


            die($html); // Kirim HTML langsung

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
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

            // Gunakan validator langsung
            $access = $this->validator->validateAccess($customer_id);
            
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

    private function getCustomerTableData($start = 0, $length = 10, $search = '', $orderColumn = 'code', $orderDir = 'asc') {
        try {
            // Validasi permission yang sudah bekerja di handleDataTableRequest()
            $hasPermission = current_user_can('view_customer_list');
            $this->logPermissionCheck(
                'view_customer_list',
                get_current_user_id(),
                0,
                null,
                $hasPermission
            );

            if (!$hasPermission) {
                return null;
            }

            // Get data using model
            $result = $this->model->getDataTableData($start, $length, $search, $orderColumn, $orderDir);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->debug_log('Error getting customer table data: ' . $e->getMessage());
            return null;
        }
    }

    // Untuk AJAX request
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            
            // Get data using shared method
            $result = $this->getCustomerTableData($start, $length, $search);
            
            if (!$result) {
                wp_send_json_error(['message' => 'Failed to get data']);
                return;
            }

            $data = [];
            foreach ($result['data'] as $customer) {  // Ini bagiannya! 
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

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
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
        // $this->debug_log("==== Generating Action Buttons for Customer ID: {$customer->id} ====");
        
        // Dapatkan relasi user dengan customer ini
        $relation = $this->validator->getUserRelation($customer->id);
        
        // Log relasi untuk debugging
        // $this->debug_log("User Relation for buttons:", $relation);

        // View Button - selalu tampilkan jika punya akses view
        if ($this->validator->canView($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button view-customer" data-id="%d">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                $customer->id
            );
        }

        // Edit Button - tampilkan jika punya akses edit
        if ($this->validator->canUpdate($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-customer" data-id="%d">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                $customer->id
            );
        }

        // Delete Button - hanya untuk admin
        if ($this->validator->canDelete($relation)) {
            $actions .= sprintf(
                '<button type="button" class="button delete-customer" data-id="%d">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                $customer->id
            );
        }
        return $actions;
    }

    /**
     * Handle customer creation request
     * Endpoint: wp_ajax_create_customer
     */
    public function store() {
        try {
            // Debug incoming data
            error_log('Create customer request data: ' . print_r($_POST, true));
            
            check_ajax_referer('wp_customer_nonce', 'nonce');

            // 1. Validasi permission terlebih dahulu
            $permission_errors = $this->validator->validatePermission('create');
            if (!empty($permission_errors)) {
                error_log('Permission validation failed: ' . print_r($permission_errors, true));
                wp_send_json_error([
                    'message' => __('Insufficient permissions', 'wp-customer'),
                    'errors' => $permission_errors
                ]);
                return;
            }

            $current_user_id = get_current_user_id();
            
            // 2. Siapkan data dasar
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'created_by' => $current_user_id,
                'provinsi_id' => isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : null,
                'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
                'status' => 'active'
            ];

            error_log('Prepared data for creation: ' . print_r($data, true));

            // 3. Handle user_id dengan beberapa skenario
            if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
                // Admin membuat customer untuk user lain
                $data['user_id'] = absint($_POST['user_id']);
            } else {
                // User yang create menjadi owner
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
                error_log('Form validation failed: ' . print_r($form_errors, true));
                wp_send_json_error([
                    'message' => implode(', ', $form_errors),
                    'errors' => $form_errors
                ]);
                return;
            }

            error_log('Attempting to create customer with data: ' . print_r($data, true));

            // 6. Buat customer baru
            $id = $this->model->create($data);
            if (!$id) {
                error_log('Failed to create customer - no ID returned');
                throw new \Exception(__('Failed to create customer', 'wp-customer'));
            }

            error_log('Customer created successfully with ID: ' . $id);

            // 7. Get fresh data untuk response
            $customer = $this->model->find($id);
            if (!$customer) {
                error_log('Failed to retrieve created customer with ID: ' . $id);
                throw new \Exception(__('Failed to retrieve created customer', 'wp-customer'));
            }

            // 8. Clear cache yang relevan
            $this->cache->invalidateCustomerListCache();
            $this->cache->invalidateCustomerStatsCache();
            if ($data['user_id']) {
                $this->cache->invalidateUserCustomersCache($data['user_id']);
            }

            // 9. Return success response
            $response_data = [
                'id' => $id,
                'customer' => $customer,
                'branch_count' => 0,
                'message' => __('Customer created successfully', 'wp-customer')
            ];
            
            error_log('Sending success response: ' . print_r($response_data, true));
            
            wp_send_json_success($response_data);

        } catch (\Exception $e) {
            error_log('Create customer error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
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

            // 1. Validasi permission terlebih dahulu
            $permission_errors = $this->validator->validatePermission('update', $id);
            if (!empty($permission_errors)) {
                wp_send_json_error([
                    'message' => reset($permission_errors)
                ]);
                return;
            }

            // 2. Siapkan data untuk validasi dan update
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'provinsi_id' => isset($_POST['provinsi_id']) ? intval($_POST['provinsi_id']) : null,
                'regency_id' => isset($_POST['regency_id']) ? intval($_POST['regency_id']) : null
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

            // 4. Lakukan update
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

            // Cek akses dulu sebelum ambil data
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                throw new \Exception('You do not have permission to view this customer');
            }

            // Cache & DB fetch dalam satu operasi
            $customer = $this->getCustomerData($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Hitung branch count sekali saja
            $branch_count = $this->model->getBranchCount($id);

            // Enrichment data
            $customer_data = $this->enrichCustomerData($customer, $access, $branch_count);

            // Debug log lebih terstruktur
            $this->logCustomerAccess($customer_data);

            wp_send_json_success($customer_data);

        } catch (\Exception $e) {
            $this->debug_log('Error in show(): ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function enrichCustomerData($customer, $access, $branch_count) {
        return [
            'customer' => array_merge((array)$customer, [
                'access_type' => $access['access_type'],
                'has_access' => $access['has_access']
            ]),
            'branch_count' => $branch_count,
            'access_type' => $access['access_type']
        ];
    }

    private function logCustomerAccess($data) {
        $this->debug_log('=== Customer Access Debug ===');
        $this->debug_log('Customer ID: ' . $data['customer']['id']);
        $this->debug_log('Access Type: ' . $data['access_type']);
        $this->debug_log('Customer Data: ' . print_r($data['customer'], true));
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

            //error_log('Query param customer_id: ' . $customer_id);

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
                // Cek apakah user adalah owner atau employee dari customer
                $relation = $this->validator->getUserRelation($id);
                if ($relation['is_owner'] || $relation['is_employee']) {
                    $customer = $this->model->find($id);
                    if ($customer) {
                        $customer->branch_count = $this->model->getBranchCount($id);
                        return $customer;
                    }
                }
                return null;
            } 

            // Untuk id spesifik
            $access = $this->validator->validateAccess($id);
            if (!$access['has_access']) {
                return null;
            }

            $customer = $this->cache->getCustomer($id) ?? $this->model->find($id);
            if ($customer) {
                $customer->branch_count = $this->model->getBranchCount($id);
                return $customer;
            }

            return null;

        } catch (\Exception $e) {
            $this->debug_log("Error getting customer data: " . $e->getMessage());
            return null;
        }
    }

    private function getCapabilityLabel($cap) {
        $labels = [
            'can_add_staff' => __('Dapat menambah staff', 'wp-customer'),
            'can_export' => __('Dapat export data', 'wp-customer'),
            'can_bulk_import' => __('Dapat bulk import', 'wp-customer')
        ];
        return $labels[$cap] ?? $cap;
    }

    private function shouldShowUpgradeOption($current_level, $target_level) {
        $levels = ['regular', 'priority', 'utama'];
        $current_idx = array_search($current_level, $levels);
        $target_idx = array_search($target_level, $levels);
        return $target_idx > $current_idx;
    }

    private function renderPlanFeatures($plan) {
        $features = [
            'regular' => [
                'Maksimal 2 staff',
                'Dapat menambah staff',
                '1 departemen'
            ],
            'priority' => [
                'Maksimal 5 staff',
                'Dapat menambah staff',
                'Dapat export data',
                '3 departemen'
            ],
            'utama' => [
                'Unlimited staff',
                'Semua fitur Priority',
                'Dapat bulk import',
                'Unlimited departemen'
            ]
        ];

        echo '<ul class="plan-features">';
        foreach ($features[$plan] as $feature) {
            echo '<li>' . esc_html($feature) . '</li>';
        }
        echo '</ul>';
    }

    public function renderMainPage() {
        global $wpdb;
        $current_user_id = get_current_user_id();

        $this->debug_log('--- Debug Controller renderMainPage ---');
        $this->debug_log('User ID: ' . $current_user_id);

        // Get customer_id dari hash URL jika ada
        $customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$customer_id && isset($_SERVER['REQUEST_URI'])) {
            if (preg_match('/#(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
                $customer_id = (int)$matches[1];
            }
        }

        // Jika ada customer_id, ambil datanya seperti di method show()
        if ($customer_id > 0) {
            // Coba ambil dari cache dulu
            $customer = $this->cache->getCustomer($customer_id);
            
            // Jika tidak ada di cache, ambil dari database
            if (!$customer) {
                $customer = $this->model->find($customer_id);
            }

            if ($customer) {
                // Validasi akses
                $access = $this->validator->validateAccess($customer_id);
                $this->logPermissionCheck(
                    'view_customer_detail',
                    $current_user_id, 
                    $customer_id,
                    null,
                    $access['has_access']
                );

                if ($access['has_access']) {
                    // Tambah informasi tambahan
                    $customer->branch_count = $this->model->getBranchCount($customer_id);
                    $customer->access_type = $access['access_type'];
                    $customer->has_access = $access['has_access'];
                }
            }
        }

        // Setup template data
        $template_data = [
            'customer' => $customer ?? null,
            'access' => $access ?? [
                'has_access' => true,
                'access_type' => current_user_can('edit_all_customers') ? 'admin' : 'owner',
                'relation' => [
                    'is_admin' => current_user_can('edit_all_customers'),
                    'is_owner' => true,
                    'is_employee' => false
                ]
            ],
            'controller' => $this,
            'branches' => [],
            'employees' => []
        ];

        if (isset($customer)) {
            $this->debug_log('Customer data loaded: ' . print_r($customer, true));
        }

        // Render template
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-dashboard.php';
    }
    
    
    
}
