<?php
/**
 * Branch Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Branch
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Branch/BranchController.php
 *
 * Description: Controller untuk mengelola data cabang.
 *              Menangani operasi CRUD dengan integrasi cache.
 *              Includes validasi input, permission checks,
 *              dan response formatting untuk DataTables.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial implementation
 * - Added CRUD endpoints
 * - Added DataTables integration
 * - Added permission checks
 * - Added cache support
 */

namespace WPCustomer\Controllers\Branch;

use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Validators\Branch\BranchValidator;
use WPCustomer\Cache\CustomerCacheManager;

class BranchController {
    private CustomerModel $customerModel;
    private BranchModel $model;
    private BranchValidator $validator;
    private CustomerCacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/branch.log';

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->model = new BranchModel();
        $this->validator = new BranchValidator();
        $this->cache = new CustomerCacheManager();

        // Initialize log file inside plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;

        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_branch_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_nopriv_handle_branch_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_customer_branches', [$this, 'getCustomerBranches']);

        // Register other endpoints
        add_action('wp_ajax_get_branch', [$this, 'show']);
        add_action('wp_ajax_create_branch', [$this, 'store']);
        add_action('wp_ajax_update_branch', [$this, 'update']);
        add_action('wp_ajax_delete_branch', [$this, 'delete']);
        add_action('wp_ajax_validate_branch_type_change', [$this, 'validateBranchTypeChange']);
    }

    public function validateBranchTypeChange() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $branch_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $new_type = isset($_POST['new_type']) ? sanitize_text_field($_POST['new_type']) : '';
            
            if (!$branch_id || !$new_type) {
                throw new \Exception('Missing required parameters');
            }

            // Get current branch data
            $branch = $this->model->find($branch_id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Get all branches for this customer
            $existing_pusat = $this->model->findPusatByCustomer($branch->customer_id);
            
            // If changing to 'pusat' and there's already a pusat branch
            if ($new_type === 'pusat' && $existing_pusat && $existing_pusat->id !== $branch_id) {
                wp_send_json_error([
                    'message' => sprintf(
                        'Customer sudah memiliki kantor pusat: %s. Tidak dapat mengubah cabang ini menjadi kantor pusat.',
                        $existing_pusat->name
                    ),
                    'original_type' => $branch->type
                ]);
            }
            
            // If changing from 'pusat' to 'cabang', check if this is the only pusat
            if ($branch->type === 'pusat' && $new_type === 'cabang') {
                $pusat_count = $this->model->countPusatByCustomer($branch->customer_id);
                if ($pusat_count <= 1) {
                    wp_send_json_error([
                        'message' => 'Tidak dapat mengubah tipe menjadi cabang karena ini adalah satu-satunya kantor pusat.',
                        'original_type' => $branch->type
                    ]);
                }
            }

            wp_send_json_success(['message' => 'Valid']);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize log directory if it doesn't exist
     */
    private function initLogDirectory(): void {
        // Gunakan wp_upload_dir() untuk mendapatkan writable directory
        $upload_dir = wp_upload_dir();
        $plugin_log_dir = $upload_dir['basedir'] . '/wp-customer/logs';
        
        // Update log file path dengan format yang lebih informatif
        $this->log_file = $plugin_log_dir . '/customer-' . date('Y-m') . '.log';

        // Buat direktori jika belum ada
        if (!file_exists($plugin_log_dir)) {
            if (!wp_mkdir_p($plugin_log_dir)) {
                // Jika gagal, gunakan sys_get_temp_dir sebagai fallback
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . '/wp-customer.log';
                error_log('Failed to create log directory in uploads: ' . $plugin_log_dir);
                return;
            }

            // Protect directory dengan .htaccess
            file_put_contents($plugin_log_dir . '/.htaccess', 'deny from all');
            chmod($plugin_log_dir, 0755);
        }

        // Buat file log jika belum ada
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
     * Log debug messages to file
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

        // Coba tulis ke file
        $written = @error_log($log_message, 3, $this->log_file);
        
        // Jika gagal, log ke default WordPress debug log
        if (!$written) {
            error_log('WP Customer Plugin: ' . $log_message);
        }
    }

    private function generateActionButtons($branch) {
        $current_user_id = get_current_user_id();
        $customer = $this->customerModel->find($branch->customer_id);
        $actions = '';

        // View button - gunakan canViewBranch
        if ($this->validator->canViewBranch($branch, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button view-branch" data-id="%d" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </button> ',
                $branch->id,
                __('Lihat', 'wp-customer')
            );
        }

        // Edit button - gunakan canEditBranch
        if ($this->validator->canEditBranch($branch, $customer)) {
            $actions .= sprintf(
                '<button type="button" class="button edit-branch" data-id="%d" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </button> ',
                $branch->id,
                __('Edit', 'wp-customer')
            );
        }

        // Delete button - gunakan canDeleteBranch
        if ($this->validator->canDeleteBranch($branch, $customer)) {
            $type_validation = $this->validator->validateBranchTypeDelete($branch->id);
            if ($type_validation['valid']) {
                $actions .= sprintf(
                    '<button type="button" class="button delete-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $branch->id,
                    __('Hapus', 'wp-customer')
                );
            }
        }

        return $actions;
    }
    public function handleDataTableRequest() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            $columns = ['name', 'type', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

            if ($orderBy === 'actions') {
                $orderBy = 'name';
            }

            try {
                $result = $this->model->getDataTableData(
                    $customer_id,
                    $start,
                    $length,
                    $search,
                    $orderBy,
                    $orderDir
                );

                if (!$result) {
                    throw new \Exception('No data returned from model');
                }

                $data = [];
                foreach ($result['data'] as $branch) {
                    // Get WordPress user data for admin name
                    $admin_name = '-';
                    if (!empty($branch->user_id)) {
                        $user = get_userdata($branch->user_id);
                        if ($user) {
                            $first_name = $user->first_name;
                            $last_name = $user->last_name;
                            if ($first_name || $last_name) {
                                $admin_name = trim($first_name . ' ' . $last_name);
                            } else {
                                $admin_name = $user->display_name;
                            }
                        }
                    }

                    $data[] = [
                        'id' => $branch->id,
                        'code' => esc_html($branch->code),
                        'name' => esc_html($branch->name),
                        'admin_name' => esc_html($admin_name),
                        'type' => esc_html($branch->type),
                        'actions' => $this->generateActionButtons($branch)
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
    

            if ($is_owner || $is_staff || current_user_can('view_branch_detail')) {
                $actions .= sprintf(
                    '<button type="button" class="button view-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-visibility"></i>
                    </button> ',
                    $branch->id,
                    __('Lihat', 'wp-customer')
                );
                $this->debug_log("Added View Button");
            }

            if (current_user_can('edit_all_branches') || 
                $is_owner || 
                (current_user_can('edit_own_branch') && (int)$branch->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button edit-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-edit"></i>
                    </button> ',
                    $branch->id,
                    __('Edit', 'wp-customer')
                );
                $this->debug_log("Added Edit Button");
            }

            // 6. Delete Button Logic
            // - Owner bisa hapus semua cabang dalam customernya
            // - Staff hanya bisa hapus cabang yang dia buat
            // - Admin dengan delete_branch bisa hapus semua
            if (current_user_can('delete_branch') || 
                $is_owner || 
                (current_user_can('delete_branch') && (int)$branch->created_by === (int)$current_user_id)) {
                
                $actions .= sprintf(
                    '<button type="button" class="button delete-branch" data-id="%d" title="%s">
                        <i class="dashicons dashicons-trash"></i>
                    </button>',
                    $branch->id,
                    __('Hapus', 'wp-customer')
                );
                $this->debug_log("Added Delete Button");
            }

            // Log final buttons
            $this->debug_log("Final action buttons HTML: " . $actions);
            $this->debug_log("==== End Action Buttons Generation ====\n");

            return $actions;
        }

        public function store() {
            try {
                check_ajax_referer('wp_customer_nonce', 'nonce');

                if (!current_user_can('add_branch')) {
                    throw new \Exception('Insufficient permissions');
                }

                // 1. Validate customer_id first
                $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
                if (!$customer_id) {
                    throw new \Exception('Invalid customer ID');
                }

                // 2. Validate branch type
                $type = sanitize_text_field($_POST['type']);
                $type_validation = $this->validator->validateBranchTypeCreate($type, $customer_id);
                if (!$type_validation['valid']) {
                    throw new \Exception($type_validation['message']);
                }

                // 3. Validate branch data
                $branch_data = [
                    'customer_id' => $customer_id,
                    'name' => sanitize_text_field($_POST['name']),
                    'type' => $type,
                    'nitku' => sanitize_text_field($_POST['nitku']),
                    'postal_code' => sanitize_text_field($_POST['postal_code']),
                    'latitude' => (float)$_POST['latitude'],
                    'longitude' => (float)$_POST['longitude'],
                    'address' => sanitize_text_field($_POST['address']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'email' => sanitize_email($_POST['email']),
                    'provinsi_id' => (int)$_POST['provinsi_id'],
                    'regency_id' => (int)$_POST['regency_id'],
                    'created_by' => get_current_user_id(),
                    'status' => 'active'
                ];

                // Validate branch data
                $branch_validation = $this->validator->validateCreate($branch_data);
                if (!empty($branch_validation)) {
                    throw new \Exception(reset($branch_validation));
                }

                // 4. Create WP User after all validations pass
                $userdata = [
                    'user_login' => sanitize_text_field($_POST['admin_username']),
                    'user_email' => sanitize_email($_POST['admin_email']),
                    'first_name' => sanitize_text_field($_POST['admin_firstname']),
                    'last_name' => sanitize_text_field($_POST['admin_lastname']),
                    'user_pass' => wp_generate_password(),
                    'role' => 'customer'
                ];

                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    throw new \Exception($user_id->get_error_message());
                }

                // 5. Add user_id to branch data and create branch
                $branch_data['user_id'] = $user_id;
                $branch_id = $this->model->create($branch_data);
                
                if (!$branch_id) {
                    wp_delete_user($user_id);
                    throw new \Exception('Failed to create branch');
                }

                // 6. Send password reset email
                wp_new_user_notification($user_id, null, 'user');

                wp_send_json_success([
                    'message' => 'Branch dan admin berhasil dibuat',
                    'branch_id' => $branch_id
                ]);

            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

    /**
     * Implementation in update() method
     */

        public function update() {
            try {
                check_ajax_referer('wp_customer_nonce', 'nonce');

                $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                if (!$id) {
                    throw new \Exception('Invalid branch ID');
                }

                // Get existing branch data
                $branch = $this->model->find($id);
                if (!$branch) {
                    throw new \Exception('Branch not found');
                }

                // Get customer data untuk permission check
                $customer = $this->customerModel->find($branch->customer_id);
                if (!$customer) {
                    throw new \Exception('Customer not found');
                }

                // Permission check di awal
                if (!$this->validator->canEditBranch($branch, $customer)) {
                    throw new \Exception('Anda tidak memiliki izin untuk mengedit cabang ini.');
                }

               // Validate input
               $data = array_filter([
                   'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : null,
                   'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : null,
                   'nitku' => isset($_POST['nitku']) ? sanitize_text_field($_POST['nitku']) : null,
                   'postal_code' => isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : null,
                   'latitude' => isset($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                   'longitude' => isset($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                   'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : null,
                   'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : null,
                   'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : null,
                   'provinsi_id' => isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : null,
                   'regency_id' => isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : null,
                   'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
                   'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null
               ], function($value) { return $value !== null; });

                // Business logic validation
                $errors = $this->validator->validateUpdate($data, $id);
                if (!empty($errors)) {
                    throw new \Exception(reset($errors));
                }

                // Update data
                $updated = $this->model->update($id, $data);
                if (!$updated) {
                    throw new \Exception('Failed to update branch');
                }

                wp_send_json_success([
                    'message' => __('Branch updated successfully', 'wp-customer'),
                    'branch' => $this->model->find($id)
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
                    throw new \Exception('Invalid branch ID');
                }

                // Get branch data
                $branch = $this->model->find($id);
                if (!$branch) {
                    throw new \Exception('Branch not found');
                }

                // Get customer data
                $customer = $this->customerModel->find($branch->customer_id);
                if (!$customer) {
                    throw new \Exception('Customer not found');
                }

                // Permission check di awal
                if (!$this->validator->canViewBranch($branch, $customer)) {
                    throw new \Exception('Anda tidak memiliki izin untuk melihat detail cabang ini.');
                }

                // Business logic validation dengan data yang sudah ada
                $errors = $this->validator->validateView($branch, $customer);
                if (!empty($errors)) {
                    throw new \Exception(reset($errors));
                }

                wp_send_json_success([
                    'branch' => $branch
                ]);

            } catch (\Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }
            
            // Get branch and customer for permission check
            $branch = $this->model->find($id);
            $customer = $this->customerModel->find($branch->customer_id);
            
            // Check edit permission first (since delete requires edit)
            if (!$this->validator->canEditBranch($branch, $customer)) {
                throw new \Exception('Permission denied');
            }
            
            // Validate branch type deletion
            $type_validation = $this->validator->validateBranchTypeDelete($id);
            if (!$type_validation['valid']) {
                throw new \Exception($type_validation['message']);
            }
            
            // Proceed with deletion
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete branch');
            }
            
            wp_send_json_success([
                'message' => __('Branch deleted successfully', 'wp-customer')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getCustomerBranches() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
            if (!$customer_id) {
                throw new \Exception('ID Customer tidak valid');
            }

            // Periksa permission
            if (!current_user_can('view_branch_list') && !current_user_can('view_own_branch')) {
                throw new \Exception('Anda tidak memiliki akses untuk melihat data cabang');
            }

            $branches = $this->model->getByCustomer($customer_id);

            wp_send_json_success($branches);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

}

