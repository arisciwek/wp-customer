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

use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Validators\Branch\BranchValidator;
use WPCustomer\Cache\CacheManager;

class BranchController {
    private BranchModel $model;
    private BranchValidator $validator;
    private CacheManager $cache;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/branch.log';

    public function __construct() {
        $this->model = new BranchModel();
        $this->validator = new BranchValidator();
        $this->cache = new CacheManager();

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
    
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get and validate customer_id
            $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
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
            $columns = ['name', 'type', 'actions'];
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'name';

            if ($orderBy === 'actions') {
                $orderBy = 'name'; // Default sort if actions column
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
                    $data[] = [
                        'id' => $branch->id,
                        'code' => esc_html($branch->code),
                        'name' => esc_html($branch->name),
                        'type' => esc_html($branch->type),
                        'customer_name' => esc_html($branch->customer_name),
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
    }

    private function generateActionButtons($branch) {
        $actions = '';

        if (current_user_can('view_branch_detail')) {
            $actions .= sprintf(
                '<button type="button" class="button view-branch" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-visibility"></i></button> ',
                $branch->id,
                __('Lihat', 'wp-customer')
            );
        }

        if (current_user_can('edit_all_branches') ||
            (current_user_can('edit_own_branch') && $branch->created_by === get_current_user_id())) {
            $actions .= sprintf(
                '<button type="button" class="button edit-branch" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-edit"></i></button> ',
                $branch->id,
                __('Edit', 'wp-customer')
            );
        }

        if (current_user_can('delete_branch')) {
            $actions .= sprintf(
                '<button type="button" class="button delete-branch" data-id="%d" title="%s">' .
                '<i class="dashicons dashicons-trash"></i></button>',
                $branch->id,
                __('Hapus', 'wp-customer')
            );
        }

        return $actions;
    }

    public function store() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('add_branch')) {
                wp_send_json_error([
                    'message' => __('Insufficient permissions', 'wp-customer')
                ]);
                return;
            }

            $data = [
                'customer_id' => intval($_POST['customer_id']),
                'code' => sanitize_text_field($_POST['code']),
                'name' => sanitize_text_field($_POST['name']),
                'type' => sanitize_text_field($_POST['type']),
                'created_by' => get_current_user_id()
            ];

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
                $this->debug_log('Failed to create branch');
                wp_send_json_error([
                    'message' => __('Failed to create branch', 'wp-customer')
                ]);
                return;
            }

            //$this->debug_log('Branch created with ID: ' . $id);

            // Get fresh data for response
            $branch = $this->model->find($id);
            if (!$branch) {
                $this->debug_log('Failed to retrieve created branch');
                wp_send_json_error([
                    'message' => __('Failed to retrieve created branch', 'wp-customer')
                ]);
                return;
            }

            wp_send_json_success([
                'message' => __('Branch created successfully', 'wp-customer'),
                'branch' => $branch
            ]);

        } catch (\Exception $e) {
            $this->debug_log('Store error: ' . $e->getMessage());
            $this->debug_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => $e->getMessage() ?: __('Failed to add branch', 'wp-customer'),
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Validate input
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'type' => sanitize_text_field($_POST['type'])
            ];

            $errors = $this->validator->validateUpdate($data, $id);
            if (!empty($errors)) {
                wp_send_json_error(['message' => implode(', ', $errors)]);
                return;
            }

            // Update data
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update branch');
            }

            // Get updated data
            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Failed to retrieve updated branch');
            }

            wp_send_json_success([
                'message' => __('Branch updated successfully', 'wp-customer'),
                'branch' => $branch
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

            $branch = $this->model->find($id);
            if (!$branch) {
                throw new \Exception('Branch not found');
            }

            // Add permission check
            if (!current_user_can('view_branch_detail') &&
                (!current_user_can('view_own_branch') || $branch->created_by !== get_current_user_id())) {
                throw new \Exception('You do not have permission to view this branch');
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

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid branch ID');
            }

            // Validate delete operation
            $errors = $this->validator->validateDelete($id);
            if (!empty($errors)) {
                throw new \Exception(reset($errors));
            }

            // Perform delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete branch');
            }

            wp_send_json_success([
                'message' => __('Data Cabang berhasil dihapus', 'wp-customer')
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
