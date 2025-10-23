<?php
/**
 * Employee Cleanup Handler
 *
 * @package     WP_Customer
 * @subpackage  Handlers
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Handlers/EmployeeCleanupHandler.php
 *
 * Description: Handles cleanup operations when employee is deleted.
 *              Task-2170: Employee Runtime Flow Synchronization
 *              - Responds to wp_customer_employee_before_delete HOOK (validation/logging)
 *              - Responds to wp_customer_employee_deleted HOOK (cache invalidation)
 *              - Employee is leaf node (no cascade delete needed)
 *
 * Changelog:
 * 1.0.0 - 2025-01-21
 * - Initial implementation (Task-2170)
 * - Added before delete handler (audit logging)
 * - Added after delete handler (cache cleanup)
 * - No cascade delete (employee is leaf node)
 */

namespace WPCustomer\Handlers;

use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class EmployeeCleanupHandler {
    private $cache_manager;

    public function __construct() {
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Handle before employee delete event
     *
     * Fires before an employee is deleted. Use for:
     * - Audit logging
     * - Validation checks
     * - Pre-deletion notifications
     *
     * @param int $employee_id Employee ID being deleted
     * @param array $employee_data Employee data before deletion
     * @return void
     *
     * @since 1.0.0
     */
    public function handleBeforeDelete(int $employee_id, array $employee_data): void {
        // Log deletion for audit trail
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Employee Delete] ID: %d, Name: %s, Customer: %d, Branch: %d',
                $employee_id,
                $employee_data['name'] ?? 'Unknown',
                $employee_data['customer_id'] ?? 0,
                $employee_data['branch_id'] ?? 0
            ));
        }

        // Fire action for extensibility (e.g., external notification systems)
        do_action('wp_customer_employee_before_delete_processing', $employee_id, $employee_data);
    }

    /**
     * Handle after employee delete event
     *
     * Fires after an employee is deleted. Performs:
     * - Cache invalidation (employee, customer, branch)
     * - DataTable cache cleanup
     * - Extended cleanup via filters
     *
     * Employee is a leaf node, so no cascade delete needed.
     *
     * @param int $employee_id Employee ID that was deleted
     * @param array $employee_data Employee data before deletion
     * @param bool $is_hard_delete Whether this was a hard delete (true) or soft delete (false)
     * @return void
     *
     * @since 1.0.0
     */
    public function handleAfterDelete(int $employee_id, array $employee_data, bool $is_hard_delete): void {
        $customer_id = $employee_data['customer_id'] ?? 0;
        $branch_id = $employee_data['branch_id'] ?? 0;

        // 1. Invalidate employee cache
        $this->cache_manager->delete('customer_employee', $employee_id);
        wp_cache_delete("customer_employee_{$employee_id}", 'wp_customer');

        // 2. Invalidate customer-level caches
        if ($customer_id) {
            $this->cache_manager->delete('customer_employee_count', (string)$customer_id);
            $this->cache_manager->delete('active_customer_employee_count', (string)$customer_id);
            $this->cache_manager->invalidateCustomerCache($customer_id);
        }

        // 3. Invalidate branch-level caches
        if ($branch_id) {
            wp_cache_delete("branch_employees_{$branch_id}", 'wp_customer');
        }

        // 4. Invalidate DataTable cache
        if ($customer_id) {
            $this->cache_manager->invalidateDataTableCache('customer_employee_list', [
                'customer_id' => $customer_id
            ]);
        }

        // 5. Invalidate user info cache (for admin bar)
        if (isset($employee_data['user_id']) && $employee_data['user_id']) {
            $this->cache_manager->delete('customer_user_info', $employee_data['user_id']);
        }

        // Log successful cleanup
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Employee Cleanup] Completed for ID: %d (%s delete)',
                $employee_id,
                $is_hard_delete ? 'hard' : 'soft'
            ));
        }

        // Fire action for extensibility
        // Allows external plugins to perform additional cleanup
        do_action('wp_customer_employee_cleanup_completed', $employee_id, $employee_data, $is_hard_delete);
    }

    /**
     * Check if hard delete is enabled in settings
     *
     * Uses same setting as Branch/Customer for consistency:
     * 'enable_hard_delete_branch' in wp_customer_general_options
     *
     * @return bool True if hard delete is enabled
     *
     * @since 1.0.0
     */
    private function isHardDeleteEnabled(): bool {
        $settings = get_option('wp_customer_general_options', []);
        return isset($settings['enable_hard_delete_branch']) && $settings['enable_hard_delete_branch'] === true;
    }
}
