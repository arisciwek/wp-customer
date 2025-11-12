<?php
/**
 * Payment Cache Manager
 *
 * @package     WP_Customer
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Cache/PaymentCacheManager.php
 *
 * Description: Cache manager untuk Payment entity.
 *              Extends AbstractCacheManager dari wp-app-core.
 *              Handles caching untuk payment data, relations, dan DataTable.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (TODO-2199)
 * - Initial implementation using CustomerCacheManager as template
 * - Extends AbstractCacheManager v1.0.1
 * - Implements 5 abstract methods
 * - Cache expiry: 6 hours (same as invoice, dynamic payment data)
 * - Cache group: wp_customer_payment
 * - Payment-specific cache keys
 */

namespace WPCustomer\Cache;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

class PaymentCacheManager extends AbstractCacheManager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return PaymentCacheManager
     */
    public static function getInstance(): PaymentCacheManager {
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
        return 'wp_customer_payment';
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
        return 'payment';
    }

    /**
     * Get cache keys mapping
     *
     * @return array
     */
    protected function getCacheKeys(): array {
        return [
            'payment' => 'payment',
            'payment_list' => 'payment_list',
            'payment_stats' => 'payment_stats',
            'payment_total_count' => 'payment_total_count',
            'payment_by_invoice' => 'payment_by_invoice',
            'payment_by_customer' => 'payment_by_customer',
            'payment_by_status' => 'payment_by_status',
            'payment_pending' => 'payment_pending',
            'payment_relation' => 'payment_relation',
            'payment_ids' => 'payment_ids',
        ];
    }

    /**
     * Get known cache types for fallback clearing
     *
     * @return array
     */
    protected function getKnownCacheTypes(): array {
        return [
            'payment',
            'payment_list',
            'payment_stats',
            'payment_total_count',
            'payment_by_invoice',
            'payment_by_customer',
            'payment_by_status',
            'payment_pending',
            'payment_relation',
            'payment_ids',
            'datatable'
        ];
    }

    // ========================================
    // CUSTOM CACHE METHODS (Entity-specific)
    // ========================================

    /**
     * Get payment from cache
     *
     * @param int $id Payment ID
     * @return object|false Payment object or FALSE if not found (cache miss)
     */
    public function getPayment(int $id): object|false {
        return $this->get('payment', $id);
    }

    /**
     * Set payment in cache
     *
     * @param int $id Payment ID
     * @param object $payment Payment data
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setPayment(int $id, object $payment, ?int $expiry = null): bool {
        return $this->set('payment', $payment, $expiry, $id);
    }

    /**
     * Get payments by invoice from cache
     *
     * @param int $invoice_id Invoice ID
     * @return array|false Array of payments or FALSE if not found
     */
    public function getPaymentsByInvoice(int $invoice_id): array|false {
        return $this->get('payment_by_invoice', $invoice_id);
    }

    /**
     * Set payments by invoice in cache
     *
     * @param int $invoice_id Invoice ID
     * @param array $payments Array of payment objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setPaymentsByInvoice(int $invoice_id, array $payments, ?int $expiry = null): bool {
        return $this->set('payment_by_invoice', $payments, $expiry, $invoice_id);
    }

    /**
     * Get payments by customer from cache
     *
     * @param int $customer_id Customer ID
     * @return array|false Array of payments or FALSE if not found
     */
    public function getPaymentsByCustomer(int $customer_id): array|false {
        return $this->get('payment_by_customer', $customer_id);
    }

    /**
     * Set payments by customer in cache
     *
     * @param int $customer_id Customer ID
     * @param array $payments Array of payment objects
     * @param int|null $expiry Optional custom expiry
     * @return bool
     */
    public function setPaymentsByCustomer(int $customer_id, array $payments, ?int $expiry = null): bool {
        return $this->set('payment_by_customer', $payments, $expiry, $customer_id);
    }

    /**
     * Get pending payments from cache
     *
     * @return array|false Array of pending payments or FALSE if not found
     */
    public function getPendingPayments(): array|false {
        return $this->get('payment_pending');
    }

    /**
     * Set pending payments in cache
     *
     * @param array $payments Array of pending payment objects
     * @param int|null $expiry Optional custom expiry (default: 5 minutes for pending status)
     * @return bool
     */
    public function setPendingPayments(array $payments, ?int $expiry = null): bool {
        $expiry = $expiry ?? (5 * MINUTE_IN_SECONDS); // 5 minutes for pending payments
        return $this->set('payment_pending', $payments, $expiry);
    }

    /**
     * Invalidate payment cache
     *
     * Clears all cache related to a specific payment:
     * - Payment entity
     * - DataTable cache
     * - Invoice's payment list
     * - Customer's payment list
     * - Stats cache
     * - Pending payments (if status changes)
     *
     * @param int $id Payment ID
     * @param int|null $invoice_id Optional invoice ID for targeted clearing
     * @param int|null $customer_id Optional customer ID for targeted clearing
     * @return void
     */
    public function invalidatePaymentCache(int $id, ?int $invoice_id = null, ?int $customer_id = null): void {
        // Clear payment entity cache
        $this->delete('payment', $id);

        // Clear DataTable cache
        $this->invalidateDataTableCache('payment_list');

        // Clear stats cache
        $this->clearCache('payment_stats');
        $this->clearCache('payment_total_count');

        // Clear payment IDs cache
        $this->delete('payment_ids', 'active');

        // Clear relation cache
        $this->clearCache('payment_relation');

        // Clear pending payments cache (important when status changes)
        $this->clearCache('payment_pending');

        // If invoice_id provided, clear invoice-specific payment cache
        if ($invoice_id) {
            $this->delete('payment_by_invoice', $invoice_id);
        }

        // If customer_id provided, clear customer-specific payment cache
        if ($customer_id) {
            $this->delete('payment_by_customer', $customer_id);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[PaymentCacheManager] Invalidated all cache for payment {$id}");
        }
    }

    /**
     * Invalidate all payments cache for an invoice
     *
     * @param int $invoice_id Invoice ID
     * @return void
     */
    public function invalidateInvoicePayments(int $invoice_id): void {
        $this->delete('payment_by_invoice', $invoice_id);
        $this->invalidateDataTableCache('payment_list');
        $this->clearCache('payment_pending');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[PaymentCacheManager] Invalidated all payments cache for invoice {$invoice_id}");
        }
    }

    /**
     * Invalidate all payments cache for a customer
     *
     * @param int $customer_id Customer ID
     * @return void
     */
    public function invalidateCustomerPayments(int $customer_id): void {
        $this->delete('payment_by_customer', $customer_id);
        $this->invalidateDataTableCache('payment_list');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[PaymentCacheManager] Invalidated all payments cache for customer {$customer_id}");
        }
    }

    /**
     * Invalidate ALL payment caches
     *
     * Clears all payment-related cache in the group.
     * Use with caution - this clears everything.
     *
     * @return bool
     */
    public function invalidateAllPaymentCache(): bool {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[PaymentCacheManager] Invalidating ALL payment caches");
        }

        return $this->clearAll();
    }
}
