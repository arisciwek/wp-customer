<?php
/**
 * Customer Cleanup Handler
 *
 * @package     WP_Customer
 * @subpackage  Handlers
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Handlers/CustomerCleanupHandler.php
 *
 * Description: Handler untuk cascade cleanup saat customer dihapus.
 *              Menangani HOOK wp_customer_deleted untuk cleanup:
 *              - Branch records terkait customer (via BranchModel::delete dengan HOOK)
 *              - Employee records (via cascade dari branch delete)
 *              - Cache invalidation
 *              - Activity logging
 *
 * Hooks:
 * - wp_customer_before_delete: Validation sebelum delete
 * - wp_customer_deleted: Cascade cleanup setelah delete
 *
 * Dependencies:
 * - BranchModel: Untuk delete branches (triggers branch delete HOOK)
 * - CustomerCacheManager: Untuk invalidate cache
 *
 * Changelog:
 * 1.0.0 - 2025-10-21
 * - Initial implementation
 * - Added cascade cleanup untuk branches (which cascades to employees)
 * - Added cache invalidation
 * - Added activity logging
 */

namespace WPCustomer\Handlers;

use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class CustomerCleanupHandler {

    private $branch_model;
    private $cache_manager;

    public function __construct() {
        $this->branch_model = new BranchModel();
        $this->cache_manager = new CustomerCacheManager();
    }

    /**
     * Handle before customer delete - validation
     *
     * @param int $customer_id Customer ID yang akan dihapus
     * @param array $customer_data Customer data (id, name, code, user_id, etc)
     * @return void
     */
    public function handleBeforeDelete(int $customer_id, array $customer_data): void {
        error_log(sprintf(
            '[CustomerCleanupHandler] Before delete customer %d (%s)',
            $customer_id,
            $customer_data['name'] ?? 'Unknown'
        ));

        // Log untuk audit trail
        do_action('wp_customer_deletion_logged', $customer_id, $customer_data, get_current_user_id());
    }

    /**
     * Handle after customer delete - cascade cleanup
     *
     * @param int $customer_id Customer ID yang sudah dihapus
     * @param array $customer_data Customer data (id, name, code, user_id, etc)
     * @param bool $is_hard_delete True jika hard delete, false jika soft delete
     * @return void
     */
    public function handleAfterDelete(int $customer_id, array $customer_data, bool $is_hard_delete = false): void {
        global $wpdb;

        error_log(sprintf(
            '[CustomerCleanupHandler] After delete customer %d (hard_delete: %s)',
            $customer_id,
            $is_hard_delete ? 'YES' : 'NO'
        ));

        // 1. Get all branches untuk customer ini
        $branches = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}app_customer_branches WHERE customer_id = %d",
            $customer_id
        ), ARRAY_A);

        error_log("[CustomerCleanupHandler] Found " . count($branches) . " branches to clean up");

        // 2. Delete all branches (this will trigger branch delete HOOK → cascade to employees)
        $deleted_branches = 0;
        foreach ($branches as $branch) {
            if ($this->branch_model->delete($branch['id'])) {
                $deleted_branches++;
            }
        }

        error_log("[CustomerCleanupHandler] Deleted {$deleted_branches} branches (employees cascaded via branch HOOK)");

        // 3. Invalidate related caches
        $this->cache_manager->invalidateCustomerCache($customer_id);
        error_log("[CustomerCleanupHandler] Invalidated caches for customer {$customer_id}");

        // 4. Clear customer-specific cache groups
        wp_cache_delete("customer_{$customer_id}", 'wp_customer');
        wp_cache_delete("customer_branches_{$customer_id}", 'wp_customer');

        // 5. Clear DataTable cache untuk customer list
        $this->cache_manager->invalidateDataTableCache('customer_list');

        // 6. Log completion
        error_log(sprintf(
            '[CustomerCleanupHandler] Cleanup completed for customer %d (%d branches deleted)',
            $customer_id,
            $deleted_branches
        ));

        // Fire action untuk extensibility (plugins lain bisa hook)
        do_action('wp_customer_cleanup_completed', $customer_id, $deleted_branches, $is_hard_delete);
    }

    /**
     * Check if hard delete is enabled
     *
     * @return bool
     */
    public function isHardDeleteEnabled(): bool {
        // Check dari settings (reuse branch setting for consistency)
        $settings = get_option('wp_customer_general_options', []);
        return isset($settings['enable_hard_delete_branch']) && $settings['enable_hard_delete_branch'] === true;
    }
}
