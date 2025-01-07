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
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Cache\CacheManager;

class CustomerController {
    private CustomerModel $model;
    private CustomerValidator $validator;
    private CacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/customer.log';

    public function __construct() {
        $this->model = new CustomerModel();
        $this->validator = new CustomerValidator();
        $this->cache = new CacheManager();

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

    }

    /**
     * Inisialisasi direktori log jika belum ada
     */
    private function initLogDirectory(): void {
        $log_dir = dirname($this->log_file);

        // Buat direktori jika belum ada
        if (!file_exists($log_dir)) {
            // Coba buat direktori dengan izin 0755
            if (!wp_mkdir_p($log_dir)) {
                // Jika gagal, gunakan sys_get_temp_dir sebagai fallback
                $this->log_file = rtrim(sys_get_temp_dir(), '/') . 'wp-customer.log';
                return;
            }

            // Set proper permissions
            chmod($log_dir, 0755);
        }

        // Buat file log jika belum ada
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
            chmod($this->log_file, 0644);
        }

        // Pastikan file bisa ditulis
        if (!is_writable($this->log_file)) {
            // Gunakan fallback ke temporary directory
            $this->log_file = rtrim(sys_get_temp_dir(), '/') . 'wp-customer.log';
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

            // Get and validate parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

            // Get order parameters
            $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Map column index to column name
            $columns = ['code', 'name', 'branch_count', 'actions'];
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
                    $data[] = [
                        'id' => $customer->id,
                        'code' => esc_html($customer->code),
                        'name' => esc_html($customer->name),
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

        if (current_user_can('view_customer_detail')) {
            $actions .= sprintf(
                '<button type="button" class="button view-customer" data-id="%d" title="%s"><i class="dashicons dashicons-visibility"></i></button> ',
                $customer->id,
                __('Lihat', 'wp-customer')
            );
        }

        if (current_user_can('edit_customer') ||
            (current_user_can('edit_own_customer') && $customer->created_by === get_current_user_id())) {
            $actions .= sprintf(
                '<button type="button" class="button edit-customer" data-id="%d" title="%s"><i class="dashicons dashicons-edit"></i></button> ',
                $customer->id,
                __('Edit', 'wp-customer')
            );
        }

        if (current_user_can('delete_customer')) {
            $actions .= sprintf(
                '<button type="button" class="button delete-customer" data-id="%d" title="%s"><i class="dashicons dashicons-trash"></i></button>',
                $customer->id,
                __('Hapus', 'wp-customer')
            );
        }

        return $actions;
    }

    public function store() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');


                // Debug current user
                $current_user = wp_get_current_user();
                $this->debug_log("Current User ID: " . $current_user->ID);
                $this->debug_log("Current User Login: " . $current_user->user_login);
                $this->debug_log("Current User Roles: " . print_r($current_user->roles, true));
                
                // Debug semua capabilities user
                $this->debug_log("All User Capabilities: " . print_r($current_user->allcaps, true));
                
                // Debug specific capability
                $this->debug_log("Has create_customer capability: " . 
                    (current_user_can('create_customer') ? 'Yes' : 'No'));
                

            if (!current_user_can('create_customer')) {
                wp_send_json_error(['message' => __('Insufficient permissions', 'wp-customer')]);
                return;
            }

            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'code' => sanitize_text_field($_POST['code']),
                'created_by' => get_current_user_id()
            ];

            // Validasi input
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
                error_log('Failed to create customer');
                wp_send_json_error([
                    'message' => __('Failed to create customer', 'wp-customer')
                ]);
                return;
            }
            // Get fresh data for response
            $customer = $this->model->find($id);
            if (!$customer) {
                error_log('Failed to retrieve created customer');
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
            error_log('Store error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
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

            // Validasi input
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'code' => sanitize_text_field($_POST['code'])  // Tambahkan ini
            ];

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

            // Get updated data
            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Failed to retrieve updated customer');
            }

            wp_send_json_success([
                'message' => 'Customer updated successfully',
                'data' => [
                    'customer' => $customer,
                    'branch_count' => $this->model->getBranchCount($id)
                ]
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function show($id) {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            if (!$id) {
                throw new \Exception('Invalid customer ID');
            }

            $customer = $this->model->find($id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Add permission check
            if (!current_user_can('view_customer_detail') &&
                (!current_user_can('view_own_customer') || $customer->created_by !== get_current_user_id())) {
                throw new \Exception('You do not have permission to view this customer');
            }

            wp_send_json_success([
                'customer' => $customer,
                'branch_count' => $this->model->getBranchCount($id)
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

}
