<?php
/**
 * Membership Level Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/MembershipLevels
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/MembershipLevels/MembershipLevelController.php
 *
 * Description: Controller untuk mengelola membership levels.
 *              Handles operasi CRUD untuk level membership.
 *              Includes validasi input dan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2024-01-29
 * - Initial implementation
 * - Added CRUD operations
 * - Added template rendering
 * - Added permission handling
 */

namespace WPCustomer\Controllers;

use WPCustomer\Models\Customer\CustomerMembershipModel;

class MembershipLevelController {
    private $model;
    private string $log_file;

    /**
     * Default log file path
     */
    private const DEFAULT_LOG_FILE = 'logs/membership.log';

    public function __construct() {
        $this->model = new CustomerMembershipModel();
        
        // Initialize log file in plugin directory
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;
        
        // Ensure logs directory exists
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_create_membership_level', [$this, 'store']);
        add_action('wp_ajax_update_membership_level', [$this, 'update']);
        add_action('wp_ajax_delete_membership_level', [$this, 'delete']);
        add_action('wp_ajax_get_membership_level', [$this, 'show']);
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
        $this->log_file = $customer_log_dir . '/membership-' . date('Y-m') . '.log';

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
     * Render main page
     */
    public function renderPage() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-customer'));
        }

        // Get all membership levels
        $levels = $this->model->getAllLevels();
        
        // Render template
        require_once WP_CUSTOMER_PATH . 'src/Views/templates/membership-levels/index.php';
    }

    /**
     * Store new membership level
     */
    public function store() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Insufficient permissions', 'wp-customer'));
            }

            // Validate and sanitize input
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'slug' => sanitize_title($_POST['slug']),
                'description' => sanitize_textarea_field($_POST['description']),
                'max_staff' => intval($_POST['max_staff']),
                'price' => floatval($_POST['price']),
                'duration' => intval($_POST['duration']),
                'capabilities' => json_encode(array_map('sanitize_text_field', $_POST['capabilities'])),
                'created_by' => get_current_user_id(),
                'status' => 'active'
            ];

            // Create level
            $id = $this->model->createLevel($data);
            if (!$id) {
                throw new \Exception('Failed to create membership level');
            }

            wp_send_json_success([
                'message' => __('Membership level created successfully', 'wp-customer'),
                'id' => $id
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update membership level
     */
    public function update() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Insufficient permissions', 'wp-customer'));
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid membership level ID');
            }

            // Validate and sanitize input
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'max_staff' => intval($_POST['max_staff']),
                'price' => floatval($_POST['price']),
                'duration' => intval($_POST['duration']),
                'capabilities' => json_encode(array_map('sanitize_text_field', $_POST['capabilities'])),
                'status' => sanitize_text_field($_POST['status'])
            ];

            // Update level
            if (!$this->model->updateLevel($id, $data)) {
                throw new \Exception('Failed to update membership level');
            }

            wp_send_json_success([
                'message' => __('Membership level updated successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show membership level details
     */
    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Insufficient permissions', 'wp-customer'));
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid membership level ID');
            }

            $level = $this->model->findLevel($id);
            if (!$level) {
                throw new \Exception('Membership level not found');
            }

            wp_send_json_success($level);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete membership level
     */
    public function delete() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Insufficient permissions', 'wp-customer'));
            }

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid membership level ID');
            }

            // Check if level is being used
            if ($this->model->isLevelInUse($id)) {
                throw new \Exception('Cannot delete level that is currently in use');
            }

            if (!$this->model->deleteLevel($id)) {
                throw new \Exception('Failed to delete membership level');
            }

            wp_send_json_success([
                'message' => __('Membership level deleted successfully', 'wp-customer')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
