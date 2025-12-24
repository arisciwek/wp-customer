<?php
/**
 * Customer Cleanup Handler
 *
 * @package     WP_Customer
 * @subpackage  Handlers
 * @version     1.0.11
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
     * @param object|array $customer_data Customer data (object from model or array)
     * @return void
     */
    public function handleBeforeDelete(int $customer_id, object|array $customer_data): void {
        // Convert object to array if needed
        $data = is_array($customer_data) ? $customer_data : (array) $customer_data;

        error_log(sprintf(
            '[CustomerCleanupHandler] Before delete customer %d (%s)',
            $customer_id,
            $data['name'] ?? 'Unknown'
        ));

        // Log untuk audit trail
        do_action('wp_customer_deletion_logged', $customer_id, $data, get_current_user_id());
    }

    /**
     * Handle after customer delete - cascade cleanup
     *
     * @param int $customer_id Customer ID yang sudah dihapus
     * @param object|array $customer_data Customer data (object from model or array)
     * @param bool $is_hard_delete True jika hard delete, false jika soft delete
     * @return void
     */
    public function handleAfterDelete(int $customer_id, object|array $customer_data, bool $is_hard_delete = false): void {
        error_log(sprintf(
            '[CustomerCleanupHandler] After delete customer %d (hard_delete: %s)',
            $customer_id,
            $is_hard_delete ? 'YES' : 'NO'
        ));

        // NOTE: Cascade delete (branches & employees) already handled by
        // CustomerDeleteHooks::cascadeDeleteRelatedEntities() in before_delete hook
        // This handler only does cache cleanup and post-delete operations

        // 1. Invalidate related caches
        $this->cache_manager->invalidateCustomerCache($customer_id);
        error_log("[CustomerCleanupHandler] Invalidated caches for customer {$customer_id}");

        // 2. Clear customer-specific cache groups
        wp_cache_delete("customer_{$customer_id}", 'wp_customer');
        wp_cache_delete("customer_branches_{$customer_id}", 'wp_customer');

        // 3. Clear DataTable cache untuk customer list
        $this->cache_manager->invalidateDataTableCache('customer_list');

        // 4. Log completion
        error_log(sprintf(
            '[CustomerCleanupHandler] Cleanup completed for customer %d',
            $customer_id
        ));

        // Fire action untuk extensibility (plugins lain bisa hook)
        do_action('wp_customer_cleanup_completed', $customer_id, 0, $is_hard_delete);
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
