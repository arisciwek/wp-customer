<?php
/**
 * Customer Demo Data Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/CustomerDemoDataController.php
 *
 * Description: Controller untuk customer demo data management.
 *              Follows pattern from PlatformDemoDataController (TODO-1207).
 *              Standalone controller (does NOT extend AbstractSettingsController).
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2201)
 * - Initial implementation following wp-app-core pattern
 * - Extracted from SettingsController (old monolithic pattern)
 * - Separate AJAX handlers for each demo data type
 * - Nonce verification per action
 * - Uses CustomerDemoData, BranchDemoData, etc. instance methods
 */

namespace WPCustomer\Controllers\Settings;

defined('ABSPATH') || exit;

class CustomerDemoDataController {

    public function __construct() {
        // No dependencies needed
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers(): void {
        // Membership Feature Groups
        add_action('wp_ajax_customer_generate_membership_groups', [$this, 'handleGenerateMembershipGroups']);

        // Membership Features
        add_action('wp_ajax_customer_generate_membership_features', [$this, 'handleGenerateMembershipFeatures']);

        // Membership Levels
        add_action('wp_ajax_customer_generate_membership_levels', [$this, 'handleGenerateMembershipLevels']);

        // Customers
        add_action('wp_ajax_customer_generate_customers', [$this, 'handleGenerateCustomers']);

        // Branches
        add_action('wp_ajax_customer_generate_branches', [$this, 'handleGenerateBranches']);

        // Employees
        add_action('wp_ajax_customer_generate_employees', [$this, 'handleGenerateEmployees']);

        // Customer Memberships
        add_action('wp_ajax_customer_generate_memberships', [$this, 'handleGenerateMemberships']);

        // Company Invoices
        add_action('wp_ajax_customer_generate_invoices', [$this, 'handleGenerateInvoices']);

        // Check demo data existence (for dependency validation)
        add_action('wp_ajax_customer_check_demo_data', [$this, 'handleCheckDemoData']);
    }

    /**
     * Handle generate membership groups
     */
    public function handleGenerateMembershipGroups(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_membership_groups')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\MembershipGroupsDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Membership groups generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate membership groups', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate membership features
     */
    public function handleGenerateMembershipFeatures(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_membership_features')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\MembershipFeaturesDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Membership features generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate membership features', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate membership levels
     */
    public function handleGenerateMembershipLevels(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_membership_levels')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\MembershipLevelsDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Membership levels generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate membership levels', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate customers
     */
    public function handleGenerateCustomers(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_customers')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\CustomerDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Customers generated successfully (with auto-created branches and employees)', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate customers', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate branches
     */
    public function handleGenerateBranches(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_branches')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\BranchDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Branches generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate branches', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate employees
     */
    public function handleGenerateEmployees(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_employees')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\CustomerEmployeeDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Employees generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate employees', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate customer memberships
     */
    public function handleGenerateMemberships(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_memberships')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\MembershipDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Customer memberships generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate customer memberships', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate company invoices
     */
    public function handleGenerateInvoices(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_generate_invoices')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $generator = new \WPCustomer\Database\Demo\CompanyInvoiceDemoData();

            if (!$generator->isDevelopmentMode()) {
                wp_send_json_error([
                    'message' => __('Development mode is not enabled', 'wp-customer')
                ]);
                return;
            }

            $success = $generator->run();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Company invoices generated successfully', 'wp-customer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to generate company invoices', 'wp-customer')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle check demo data existence
     * Used for dependency validation in frontend
     */
    public function handleCheckDemoData(): void {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_check_demo_data')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-customer')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-customer')]);
        }

        try {
            $type = sanitize_text_field($_POST['type'] ?? '');

            global $wpdb;
            $exists = false;

            switch ($type) {
                case 'membership-groups':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_membership_groups");
                    $exists = $count > 0;
                    break;

                case 'membership-features':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_membership_features");
                    $exists = $count > 0;
                    break;

                case 'customer':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customers");
                    $exists = $count > 0;
                    break;

                case 'branch':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches");
                    $exists = $count > 0;
                    break;

                case 'memberships':
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_memberships");
                    $exists = $count > 0;
                    break;

                default:
                    $exists = false;
            }

            wp_send_json_success([
                'exists' => $exists,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error: %s', 'wp-customer'), $e->getMessage())
            ]);
        }
    }
}
