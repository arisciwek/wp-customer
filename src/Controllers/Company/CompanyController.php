<?php

/**
 * Company Controller Class
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Company
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Company/CompanyController.php
 *
 * Description: Controller untuk CRUD operations data perusahaan (branch).
 *              Menangani view operations dengan integrasi cache.
 *              Includes validasi akses dan permission checks.
 *              Membership-related handlers forwarded ke CompanyMembershipController.
 *
 * NOTE: DataTable dan Statistics handlers dipindahkan ke CompanyDashboardController
 *       untuk menghindari konflik AJAX handlers dengan DualPanel framework.
 *
 * Changelog:
 * 1.0.12 - 2025-11-09 (TODO-2195)
 * - Removed: handle_company_datatable AJAX handler (moved to CompanyDashboardController)
 * - Removed: get_company_stats AJAX handler (moved to CompanyDashboardController)
 * - Fixed: AJAX handler conflicts with CompanyDashboardController
 * - Reason: Separation of concerns - CRUD vs Dashboard operations
 *
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added view functionality
 * - Added DataTable integration
 * - Added permission validation
 */

namespace WPCustomer\Controllers\Company;

use WPCustomer\Models\Company\CompanyModel;
use WPCustomer\Controllers\Company\CompanyMembershipController;
use WPCustomer\Cache\CustomerCacheManager;
use WPCustomer\Validators\Branch\BranchValidator;
use WPCustomer\Validators\Company\CompanyValidator;

class CompanyController {
    private $error_messages;
    private CompanyModel $model;
    private CustomerCacheManager $cache;
    private string $log_file;
    private BranchValidator $branchValidator;
    private CompanyValidator $companyValidator;
    private CompanyMembershipController $membershipController;

    private const DEFAULT_LOG_FILE = 'logs/company.log';

    public function __construct() {
        $this->model = new CompanyModel();
        $this->branchValidator = new BranchValidator();
        $this->companyValidator = new CompanyValidator();
        $this->cache = new CustomerCacheManager();
        $this->membershipController = new CompanyMembershipController();

        // Initialize error messages
        $this->error_messages = [
            'insufficient_permissions' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
            'view_denied' => __('Anda tidak memiliki izin untuk melihat data ini', 'wp-customer'),
        ];

        // Initialize log file
        $this->log_file = WP_CUSTOMER_PATH . self::DEFAULT_LOG_FILE;
        $this->initLogDirectory();

        // Register AJAX handlers for CRUD operations
        add_action('wp_ajax_get_company', [$this, 'show']);
        add_action('wp_ajax_validate_company_access', [$this, 'validateCompanyAccess']);

        // NOTE: DataTable and Stats handlers removed - now handled by CompanyDashboardController
        // - handle_company_datatable -> CompanyDashboardController
        // - get_company_stats -> CompanyDashboardController

        // Register membership AJAX handlers - forwarding to membership controller
        add_action('wp_ajax_get_company_membership_status', [$this, 'get_company_membership_status']);    
        add_action('wp_ajax_get_company_upgrade_options', [$this, 'get_company_upgrade_options']);
        add_action('wp_ajax_request_upgrade_company_membership', [$this, 'request_upgrade_company_membership']);
        add_action('wp_ajax_check_upgrade_eligibility_company_membership', [$this, 'check_upgrade_eligibility_company_membership']);
        add_action('wp_ajax_get_all_membership_levels', [$this, 'get_all_membership_levels']);
        
    }

    /**
     * Forward request untuk mendapatkan semua level membership ke controller membership
     */
    public function get_all_membership_levels() {
        $this->debug_log("Forwarding get_all_membership_levels to membership controller");
        $this->membershipController->getAllMembershipLevels();
    }
    
    /**
     * Forward membership status request to membership controller
     */
    public function get_company_membership_status() {
        $this->debug_log("Forwarding get_company_membership_status to membership controller");
        $this->membershipController->getMembershipStatus();
    }

    /**
     * Forward upgrade options request to membership controller
     */
    public function get_company_upgrade_options() {
        $this->debug_log("Forwarding get_company_upgrade_options to membership controller");
        $this->membershipController->getUpgradeOptions();
    }

    /**
     * Forward upgrade request to membership controller
     */
    public function request_upgrade_company_membership() {
        $this->debug_log("Forwarding request_upgrade_company_membership to membership controller");
        $this->membershipController->requestUpgradeMembership();
    }

    /**
     * Forward eligibility check to membership controller
     */
    public function check_upgrade_eligibility_company_membership() {
        $this->debug_log("Forwarding check_upgrade_eligibility_company_membership to membership controller");
        $this->membershipController->checkUpgradeEligibility();
    }

    // Rest of your CompanyController methods...

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

            $this->debug_log("Company ID requested: " . $id);
            $this->debug_log("Current user ID: " . get_current_user_id());

            // Validate basic capability
            if (!current_user_can('view_customer_branch_list')) {
                $this->debug_log("User does not have view_customer_branch_list capability");
                throw new \Exception('You do not have permission to view this data');
            }

            // Validate specific access to this company/branch
            $this->debug_log("Calling validateAccess for branch ID: " . $id);

            $access = $this->branchValidator->validateAccess($id);

            $this->debug_log("Access validation result: " . print_r($access, true));

            if (!$access['has_access']) {
                $this->debug_log("Access denied for branch ID: " . $id . " - access_type: " . ($access['access_type'] ?? 'none'));
                throw new \Exception(__('Anda tidak memiliki akses untuk melihat company ini', 'wp-customer'));
            }

            $this->debug_log("Access granted with access_type: " . ($access['access_type'] ?? 'unknown'));

            // Get company data with latest membership
            $company = $this->model->getBranchWithLatestMembership($id);
            if (!$company) {
                $this->debug_log("Company not found for ID: " . $id);
                throw new \Exception('Company not found');
            }

            $this->debug_log("Company data retrieved successfully");

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
    // REMOVED: handleDataTableRequest() - moved to CompanyDashboardController
    // REMOVED: generateActionButtons() - moved to CompanyDashboardController

    /**
     * Render main page template with enhanced debugging
     */
    public function renderMainPage() {
        $this->debug_log("\n\n=== RENDER MAIN PAGE CALLED ===");
        $this->debug_log("Current URL: " . $_SERVER['REQUEST_URI']);
        $this->debug_log("Action: " . ($_GET['action'] ?? 'none'));
        
        // Check access
        if (!$this->companyValidator->canAccessCompanyPage()) {
            $this->debug_log("RENDERING: company-no-access.php");
            require_once WP_CUSTOMER_PATH . 'src/Views/templates/company/company-no-access.php';
            return;
        }
        
        $this->debug_log("RENDERING: company-dashboard.php");
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

    /**
     * Validate company access - public endpoint untuk AJAX
     * @since 1.0.0
     */
    public function validateCompanyAccess() {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            $company_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if (!$company_id) {
                throw new \Exception('Invalid company ID');
            }

            // Gunakan validator langsung
            $access = $this->branchValidator->validateAccess($company_id);

            if (!$access['has_access']) {
                wp_send_json_error([
                    'message' => __('Anda tidak memiliki akses ke company ini', 'wp-customer'),
                    'code' => 'access_denied'
                ]);
                return;
            }

            wp_send_json_success([
                'message' => 'Akses diberikan',
                'company_id' => $company_id,
                'access_type' => $access['access_type']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => 'error'
            ]);
        }
    }

    // REMOVED: getStats() - moved to CompanyDashboardController
}
