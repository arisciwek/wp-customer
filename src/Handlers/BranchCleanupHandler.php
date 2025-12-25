<?php
/**
 * Branch Cleanup Handler
 *
 * @package     WP_Customer
 * @subpackage  Handlers
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Handlers/BranchCleanupHandler.php
 *
 * Description: Handler untuk cascade cleanup saat branch dihapus.
 *              Menangani HOOK wp_customer_branch_deleted untuk cleanup:
 *              - Employee records terkait branch
 *              - Membership records (optional, bisa soft delete)
 *              - Cache invalidation
 *              - Activity logging
 *
 * Hooks:
 * - wp_customer_branch_before_delete: Validation sebelum delete
 * - wp_customer_branch_deleted: Cascade cleanup setelah delete
 *
 * Dependencies:
 * - CustomerEmployeeModel: Untuk delete employees
 * - CustomerCacheManager: Untuk invalidate cache
 *
 * Changelog:
 * 1.0.0 - 2025-10-21
 * - Initial implementation
 * - Added cascade cleanup untuk employees
 * - Added cache invalidation
 * - Added activity logging
 */

namespace WPCustomer\Handlers;

use WPCustomer\Models\Employee\CustomerEmployeeModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class BranchCleanupHandler {

    private $employee_model;
    private $cache_manager;

    public function __construct() {
        $this->employee_model = new CustomerEmployeeModel();
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Handle before branch delete - validation
     *
     * @param int $branch_id Branch ID yang akan dihapus
     * @param stdClass|array $branch_data Branch data (stdClass from Model, array from direct calls)
     * @return void
     */
    public function handleBeforeDelete(int $branch_id, $branch_data): void {
        // Convert stdClass to array if needed (AbstractCrudModel passes stdClass)
        if (is_object($branch_data)) {
            $branch_data = (array) $branch_data;
        }

        error_log(sprintf(
            '[BranchCleanupHandler] Before delete branch %d (%s, type: %s)',
            $branch_id,
            $branch_data['name'] ?? 'Unknown',
            $branch_data['type'] ?? 'Unknown'
        ));

        // Log untuk audit trail
        do_action('wp_customer_branch_deletion_logged', $branch_id, $branch_data, get_current_user_id());
    }

    /**
     * Handle after branch delete - cascade cleanup
     *
     * @param int $branch_id Branch ID yang sudah dihapus
     * @param stdClass|array $branch_data Branch data (stdClass from Model, array from direct calls)
     * @param bool $is_hard_delete True jika hard delete, false jika soft delete
     * @return void
     */
    public function handleAfterDelete(int $branch_id, $branch_data, bool $is_hard_delete = false): void {
        global $wpdb;

        // Convert stdClass to array if needed (AbstractCrudModel passes stdClass)
        if (is_object($branch_data)) {
            $branch_data = (array) $branch_data;
        }

        error_log(sprintf(
            '[BranchCleanupHandler] After delete branch %d (hard_delete: %s)',
            $branch_id,
            $is_hard_delete ? 'YES' : 'NO'
        ));

        $customer_id = $branch_data['customer_id'] ?? 0;

        // 1. Cleanup employees
        if ($is_hard_delete) {
            // Hard delete: Actual DELETE dari database
            $deleted_employees = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}app_customer_employees WHERE branch_id = %d",
                $branch_id
            ));
            error_log("[BranchCleanupHandler] Hard deleted {$deleted_employees} employees");
        } else {
            // Soft delete: Set status='inactive'
            $updated_employees = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}app_customer_employees
                 SET status = 'inactive', updated_at = %s
                 WHERE branch_id = %d",
                current_time('mysql'),
                $branch_id
            ));
            error_log("[BranchCleanupHandler] Soft deleted (inactive) {$updated_employees} employees");
        }

        // 2. Invalidate related caches
        // Delete branch cache using CustomerCacheManager::delete()
        $this->cache_manager->delete('customer_branch', $branch_id);

        if ($customer_id) {
            $this->cache_manager->invalidateCustomerCache($customer_id);
        }
        error_log("[BranchCleanupHandler] Invalidated caches for branch {$branch_id} and customer {$customer_id}");

        // 3. Clear branch-specific cache groups
        wp_cache_delete("branch_{$branch_id}", 'wp_customer');
        wp_cache_delete("branch_employees_{$branch_id}", 'wp_customer');

        // 4. Clear DataTable cache untuk branch list
        $cache_patterns = [
            'customer_branch_list',
            'customer_employee_list'
        ];

        foreach ($cache_patterns as $pattern) {
            $this->cache_manager->invalidateDataTableCache($pattern, [
                'customer_id' => $customer_id,
                'branch_id' => $branch_id
            ]);
        }

        // 5. Log completion
        error_log(sprintf(
            '[BranchCleanupHandler] Cleanup completed for branch %d (customer: %d)',
            $branch_id,
            $customer_id
        ));

        // Fire action untuk extensibility (plugins lain bisa hook)
        do_action('wp_customer_branch_cleanup_completed', $branch_id, $customer_id, $is_hard_delete);
    }

    /**
     * Check if hard delete is enabled
     *
     * @return bool
     */
    public function isHardDeleteEnabled(): bool {
        // Check dari settings
        $settings = get_option('wp_customer_general_options', []);
        return isset($settings['enable_hard_delete_branch']) && $settings['enable_hard_delete_branch'] === true;
    }
}
