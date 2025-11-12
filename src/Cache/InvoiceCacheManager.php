<?php
/**
 * Invoice Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/InvoiceCacheManager.php
 *
 * Description: Cache manager untuk Invoice entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk invoice data, relations, dan DataTable.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2199)
 * - Initial implementation using CustomerCacheManager as template
 * - Extends AbstractCacheManager v1.0.1
 * - Implements 5 abstract methods
 * - Cache expiry: 6 hours (shorter than customer, dynamic invoice data)
 * - Cache group: wp_customer_invoice
 * - Invoice-specific cache keys
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class InvoiceCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return InvoiceCacheManager
     */
    public static function getInstance(): InvoiceCacheManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (5 required)
    // ========================================

    /**
     * Get cache group name
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_customer_invoice';
    }

    /**
     * Get cache expiry time
     *
     * @return int Cache expiry in seconds (6 hours)
     */
    protected function getCacheExpiry(): int {
        return 6 * HOUR_IN_SECONDS;
    }

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'invoice';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'invoice' => 'invoice',
            'invoice_list' => 'invoice_list',
            'invoice_stats' => 'invoice_stats',
            'invoice_total_count' => 'invoice_total_count',
            'invoice_by_customer' => 'invoice_by_customer',
            'invoice_by_status' => 'invoice_by_status',
            'invoice_by_number' => 'invoice_by_number',
            'invoice_next_number' => 'invoice_next_number',
            'invoice_relation' => 'invoice_relation',
            'invoice_ids' => 'invoice_ids',
            'number_exists' => 'number_exists',
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'invoice',
            'invoice_list',
            'invoice_stats',
            'invoice_total_count',
            'invoice_by_customer',
            'invoice_by_status',
            'invoice_by_number',
            'invoice_next_number',
            'invoice_relation',
            'invoice_ids',
            'number_exists',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get invoice from cache
     *
     * @param int $id Invoice ID
     * @return object|false Invoice object or FALSE if not found (cache miss)
     */
    public function getInvoice(int $id): object|false {
        return $this->get('invoice', $id);
    }

    /**
     * Set invoice in cache
     *
     * @param int $id Invoice ID
     * @param object $invoice Invoice data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setInvoice(int $id, object $invoice, ?int $expiry = null): bool {
        return $this->set('invoice', $invoice, $expiry, $id);
    }

    /**
     * Get invoice by number from cache
     *
     * @param string $invoice_number Invoice number
     * @return object|false Invoice object or FALSE if not found
     */
    public function getInvoiceByNumber(string $invoice_number): object|false {
        return $this->get('invoice_by_number', $invoice_number);
    }

    /**
     * Set invoice by number in cache
     *
     * @param string $invoice_number Invoice number
     * @param object $invoice Invoice data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setInvoiceByNumber(string $invoice_number, object $invoice, ?int $expiry = null): bool {
        return $this->set('invoice_by_number', $invoice, $expiry, $invoice_number);
    }

    /**
     * Get invoices by customer from cache
     *
     * @param int $customer_id Customer ID
     * @return array|false Array of invoices or FALSE if not found
     */
    public function getInvoicesByCustomer(int $customer_id): array|false {
        return $this->get('invoice_by_customer', $customer_id);
    }

    /**
     * Set invoices by customer in cache
     *
     * @param int $customer_id Customer ID
     * @param array $invoices Array of invoice objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setInvoicesByCustomer(int $customer_id, array $invoices, ?int $expiry = null): bool {
        return $this->set('invoice_by_customer', $invoices, $expiry, $customer_id);
    }

    /**
     * Get next invoice number from cache
     *
     * @param string $prefix Invoice prefix (e.g., 'INV-202501')
     * @return int|false Next number or FALSE if not found
     */
    public function getNextInvoiceNumber(string $prefix): int|false {
        return $this->get('invoice_next_number', $prefix);
    }

    /**
     * Set next invoice number in cache
     *
     * @param string $prefix Invoice prefix
     * @param int $next_number Next number
     * @param int|null $expiry Optional custom expiry (default: 1 hour for number generation)
     * @return bool
     */
    public function setNextInvoiceNumber(string $prefix, int $next_number, ?int $expiry = null): bool {
        $expiry = $expiry ?? HOUR_IN_SECONDS; // 1 hour for next number cache
        return $this->set('invoice_next_number', $next_number, $expiry, $prefix);
    }

    /**
     * Invalidate invoice cache
     *
     * Clears all cache related to a specific invoice:
     * - Invoice entity
     * - DataTable cache
     * - Customer's invoice list
     * - Stats cache
     * - Next number cache (if status changes)
     *
     * @param int $id Invoice ID
     * @param int|null $customer_id Optional customer ID for targeted clearing
     * @return void
     */
    public function invalidateInvoiceCache(int $id, ?int $customer_id = null): void {
        // Clear invoice entity cache
        $this->delete('invoice', $id);

        // Clear DataTable cache
        $this->invalidateDataTableCache('invoice_list');

        // Clear stats cache
        $this->clearCache('invoice_stats');
        $this->clearCache('invoice_total_count');

        // Clear invoice IDs cache
        $this->delete('invoice_ids', 'active');

        // Clear relation cache
        $this->clearCache('invoice_relation');

        // Clear next number cache (important when invoice created/deleted)
        $this->clearCache('invoice_next_number');

        // If customer_id provided, clear customer-specific invoice cache
        if ($customer_id) {
            $this->delete('invoice_by_customer', $customer_id);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[InvoiceCacheManager] Invalidated all cache for invoice {$id}");
        }
    }

    /**
     * Invalidate all invoices cache for a customer
     *
     * @param int $customer_id Customer ID
     * @return void
     */
    public function invalidateCustomerInvoices(int $customer_id): void {
        $this->delete('invoice_by_customer', $customer_id);
        $this->invalidateDataTableCache('invoice_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[InvoiceCacheManager] Invalidated all invoices cache for customer {$customer_id}");
        }
    }

    /**
     * Invalidate ALL invoice caches
     *
     * Clears all invoice-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllInvoiceCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[InvoiceCacheManager] Invalidating ALL invoice caches");
        }

        return $this->clearAll();
    }
}
