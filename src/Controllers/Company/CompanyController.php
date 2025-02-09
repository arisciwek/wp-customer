<?php

/**
 * Company Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyController.php
 *
 * Description: Controller untuk menampilkan data perusahaan.
 *              Menangani view operations dengan integrasi cache.
 *              Includes validasi akses dan permission checks.
 *              Menyediakan endpoints untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added view functionality
 * - Added DataTable integration
 * - Added permission validation
 */

namespace WPCustomer\Controllers\Company;

use WPCustomer\Models\Company\CompanyModel;
use WPCustomer\Cache\CustomerCacheManager;

class CompanyController {
    private $error_messages;
    private CompanyModel $model;
    private CustomerCacheManager $cache;
    private string $log_file;

    private const DEFAULT_LOG_FILE = 'logs/company.log';

    public function __construct() {
        $this->model = new CompanyModel();
        $this->cache = new CustomerCacheManager();

        // Initialize error messages
        $this->error_messages = [
            'insufficient_permissions' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
            'view_denied' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
        ];

        // Initialize log file
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;
        $this->initLogDirectory();

        // Register AJAX handlers
        add_action('wp_ajax_handle_company_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_company', [$this, 'show']);
        add_action('wp_ajax_validate_company_access', [$this, 'validateCompanyAccess']);
    }

    /**
     * Handle company view request
     * Endpoint: wp_ajax_get_company
     */
    public function show() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $this->debug_log("=== Start show() ===");
            
            // Get and validate ID
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id) {
                throw new \Exception('Invalid company ID');
            }

            // Validate access
            if (!current_user_can('view_branch_list')) {
                throw new \Exception('You do not have permission to view this data');
            }

            // Get company data with latest membership
            $company = $this->model->getBranchWithLatestMembership($id);
            if (!$company) {
                throw new \Exception('Company not found');
            }

            wp_send_json_success([
                'company' => $company
            ]);

        } catch (\Exception $e) {
            $this->debug_log("Error in show(): " . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handleDataTableRequest() {
        try {
            if (!check_ajax_referer('wp_customer_nonce', 'nonce', false)) {
                throw new \Exception('Security check failed');
            }

            // Get parameters
            $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
            $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
            $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
            $search = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
            
            // Order parameters
            $orderColumn = isset($_POST['order'][0]['column']) && isset($_POST['columns'][$_POST['order'][0]['column']]['data']) 
                ? sanitize_text_field($_POST['columns'][$_POST['order'][0]['column']]['data'])
                : 'name';
            $orderDir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';

            // Check cache
            $cached_result = $this->cache->getDataTableCache(
                'company_list',
                get_current_user_id(),
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir
            );

            if ($cached_result) {
                wp_send_json($cached_result);
                return;
            }

            // Get fresh data
            $result = $this->model->getDataTableData($start, $length, $search, $orderColumn, $orderDir);
            if (!$result) {
                throw new \Exception('Failed to fetch company data');
            }

            // Format response
            $response = [
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => array_map(function($company) {
                    return [
                        'id' => $company->id,
                        'code' => esc_html($company->code),
                        'name' => esc_html($company->name),
                        'type' => esc_html($company->type),
                        'level_name' => esc_html($company->level_name ?? '-'),
                        'actions' => $this->generateActionButtons($company)
                    ];
                }, $result['data'])
            ];

            // Set cache
            $this->cache->setDataTableCache(
                'company_list',
                get_current_user_id(),
                $start,
                $length,
                $search,
                $orderColumn,
                $orderDir,
                $response
            );

            wp_send_json($response);

        } catch (\Exception $e) {
            $this->debug_log('DataTable error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate action buttons for DataTable
     */
    private function generateActionButtons($company): string {
        if (!current_user_can('view_branch_list')) {
            return '';
        }

        return sprintf(
            '<button type="button" class="button view-company" data-id="%d">' .
            '<i class="dashicons dashicons-visibility"></i></button>',
            $company->id
        );
    }

    /**
     * Render main page template
     */
    public function renderMainPage() {
        if (!current_user_can('view_branch_list')) {
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/company/company-no-access.php';
            return;
        }

        require_once WP_CUSTOMER_PATH . 'src/Views/templates/company/company-dashboard.php';
    }

    /**
     * Initialize log directory
     */
    private function initLogDirectory(): void {
        $upload_dir = wp_upload_dir();
        $company_log_dir = $upload_dir['basedir'] . '/wp-customer/logs';
        
        if (!file_exists($company_log_dir)) {
            wp_mkdir_p($company_log_dir);
        }

        $this->log_file = $company_log_dir . '/company-' . date('Y-m') . '.log';
    }

    /**
     * Debug logging
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
}
