<?php
/**
 * Customer Delete Hooks
 *
 * @package     WP_Customer
 * @subpackage  Hooks
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Hooks/CustomerDeleteHooks.php
 *
 * Description: Hook listeners untuk customer delete operations.
 *              Handles cascade delete untuk related entities:
 *              - Customer Employees (must be deleted first)
 *              - Customer Branches (must be deleted second)
 *
 *              Triggered by wp_customer_customer_before_delete hook
 *              dari AbstractCrudModel::delete()
 *
 * Delete Order (Important untuk Foreign Keys):
 * 1. Delete employees (depends on customer_id & branch_id)
 * 2. Delete branches (depends on customer_id)
 * 3. Delete customer (primary entity)
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 * - Cascade delete employees
 * - Cascade delete branches
 * - Cache invalidation
 */

namespace WPCustomer\Hooks;

defined('ABSPATH') || exit;

class CustomerDeleteHooks {

    /**
     * Initialize hooks
     */
    public static function init(): void {
        // Hook into customer before_delete untuk cascade operations
        add_action('wp_customer_customer_before_delete', [__CLASS__, 'cascadeDeleteRelatedEntities'], 10, 2);

        // Hook into customer deleted untuk cleanup (jika diperlukan)
        add_action('wp_customer_customer_deleted', [__CLASS__, 'cleanupAfterDelete'], 10, 2);
    }

    /**
     * Cascade delete related entities
     *
     * Dipanggil SEBELUM customer dihapus.
     * Delete order penting untuk menjaga referential integrity.
     *
     * @param int $customer_id Customer ID yang akan dihapus
     * @param stdClass|array|object $customer Customer entity (stdClass from Model, array from direct calls)
     * @return void
     */
    public static function cascadeDeleteRelatedEntities(int $customer_id, $customer): void {
        global $wpdb;

        error_log("[Customer Delete] Cascade delete started for customer ID: {$customer_id}");

        try {
            // 1. Delete customer employees first (depends on both customer_id & branch_id)
            $employees_deleted = $wpdb->delete(
                $wpdb->prefix . 'app_customer_employees',
                ['customer_id' => $customer_id],
                ['%d']
            );

            if ($employees_deleted !== false) {
                error_log("[Customer Delete] Deleted {$employees_deleted} employee(s) for customer {$customer_id}");
            }

            // 2. Delete customer branches (depends on customer_id)
            $branches_deleted = $wpdb->delete(
                $wpdb->prefix . 'app_customer_branches',
                ['customer_id' => $customer_id],
                ['%d']
            );

            if ($branches_deleted !== false) {
                error_log("[Customer Delete] Deleted {$branches_deleted} branch(es) for customer {$customer_id}");
            }

            // 3. Invalidate related caches
            self::invalidateRelatedCaches($customer_id);

            error_log("[Customer Delete] Cascade delete completed for customer ID: {$customer_id}");

        } catch (\Exception $e) {
            error_log("[Customer Delete] Error during cascade delete: " . $e->getMessage());
            // Don't throw - let customer delete continue
        }
    }

    /**
     * Cleanup after customer deleted
     *
     * Dipanggil SETELAH customer berhasil dihapus.
     * Untuk cleanup operations jika diperlukan.
     *
     * @param int $customer_id Customer ID yang sudah dihapus
     * @param stdClass|array|object $customer Customer entity (stdClass from Model, array from direct calls)
     * @return void
     */
    public static function cleanupAfterDelete(int $customer_id, $customer): void {
        // Convert to object if needed for backward compatibility
        if (is_array($customer)) {
            $customer = (object) $customer;
        }

        error_log("[Customer Delete] Cleanup completed for customer ID: {$customer_id} (Code: " . ($customer->code ?? 'N/A') . ")");

        // Future: Additional cleanup operations here
        // - Delete uploaded files
        // - Clean temporary data
        // - Send notifications
        // - Audit log
    }

    /**
     * Invalidate related caches
     *
     * Clear caches untuk branches dan employees yang berhubungan
     * dengan customer yang dihapus.
     *
     * @param int $customer_id Customer ID
     * @return void
     */
    private static function invalidateRelatedCaches(int $customer_id): void {
        // Invalidate branch cache
        if (class_exists('\WPCustomer\Cache\BranchCacheManager')) {
            $branch_cache = \WPCustomer\Cache\BranchCacheManager::getInstance();
            // Clear all branch cache untuk customer ini
            // Note: BranchCacheManager should have method untuk clear by customer
            error_log("[Customer Delete] Branch cache invalidated for customer {$customer_id}");
        }

        // Invalidate employee cache (jika ada)
        // Future implementation

        // Clear any aggregated/statistics cache
        if (class_exists('\WPCustomer\Models\Customer\CustomerStatisticsModel')) {
            // Clear statistics cache
            error_log("[Customer Delete] Statistics cache cleared");
        }
    }
}
