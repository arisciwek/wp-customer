<?php
/**
 * Company Membership Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyMembershipModel.php
 *
 * Description: Model untuk mengelola data active membership customer.
 *              Menangani operasi terkait status, periode, dan pembayaran.
 *              Includes:
 *              - Manajemen status membership (active, expired, grace period)
 *              - Perhitungan periode dan tanggal
 *              - Proses upgrade membership
 *              - Integrasi dengan sistem pembayaran dasar
 *              - Cache management untuk optimasi performa
 *              
 * Dependencies:
 * - WPCustomer\Cache\CustomerCacheManager
 * - WPCustomer\Models\Customer\CustomerModel
 * - WordPress $wpdb
 * 
 * Changelog:
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added core membership operations
 * - Added status management
 * - Added period calculations
 * - Added payment tracking
 * - Added upgrade handling
 */

namespace WPCustomer\Models\Company;

use WPCustomer\Cache\CustomerCacheManager;

class CompanyMembershipModel {
    /**
     * Database table names
     * @var string
     */
    private $table;
    private $levels_table;

    /**
     * Cache manager instance
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_memberships';
        $this->levels_table = $wpdb->prefix . 'app_customer_membership_levels';
        $this->cache = new CustomerCacheManager();
    }

    public function findByCompany($company_id) {
        global $wpdb;
        
        // Debug log
        error_log("CompanyMembershipModel::findByCompany - Searching for branch_id: " . $company_id);
        
        $table = $wpdb->prefix . 'app_customer_memberships';
        
        // Pastikan query benar mencari berdasarkan branch_id
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE branch_id = %d AND status = 'active'
             ORDER BY created_at DESC 
             LIMIT 1",
            $company_id
        );
        
        error_log("Query executed: " . $query);
        
        $result = $wpdb->get_row($query);
        
        if (!$result) {
            error_log("No active membership found for branch_id: " . $company_id);
        } else {
            error_log("Found membership for branch_id: " . $company_id . ", ID: " . $result->id);
        }
        
        return $result;
    }


    /**
     * Create new membership
     *
     * @param array $data Membership data
     * @return int|false New membership ID or false on failure
     */
    public function create(array $data) {
        global $wpdb;

        // Set default values
        $data = wp_parse_args($data, [
            'status' => 'active',
            'period_months' => 1,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'payment_status' => 'pending'
        ]);

        // Calculate dates
        if (empty($data['start_date'])) {
            $data['start_date'] = current_time('mysql');
        }
        
        $data['end_date'] = $this->calculateEndDate($data['start_date'], $data['period_months']);

        // If trial available, set trial end date
        if (!empty($data['trial_days'])) {
            $data['trial_end_date'] = date('Y-m-d H:i:s', 
                strtotime($data['start_date'] . ' +' . $data['trial_days'] . ' days')
            );
        }

        // Insert to database
        $result = $wpdb->insert($this->table, $data);
        if ($result === false) {
            return false;
        }

        $new_id = $wpdb->insert_id;

        // Clear related caches
        $this->clearCache($new_id);
        if (!empty($data['company_id'])) {
            $this->cache->delete('company_membership', $data['company_id']);
        }

        return $new_id;
    }

    /**
     * Update membership
     *
     * @param int $id Membership ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        // Add updated timestamp
        $data['updated_at'] = current_time('mysql');

        // Update database
        $result = $wpdb->update(
            $this->table,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            $this->clearCache($id);
            return true;
        }

        return false;
    }

    /**
     * Activate membership
     *
     * @param int $id Membership ID
     * @return bool Success status
     */
    public function activate(int $id): bool {
        return $this->update($id, [
            'status' => 'active',
            'grace_period_end_date' => null
        ]);
    }

    /**
     * Deactivate membership
     *
     * @param int $id Membership ID
     * @return bool Success status
     */
    public function deactivate(int $id): bool {
        return $this->update($id, ['status' => 'expired']);
    }

    /**
     * Start grace period for membership
     *
     * @param int $id Membership ID
     * @return bool Success status
     */
    public function startGracePeriod(int $id): bool {
        $membership = $this->find($id);
        if (!$membership) {
            return false;
        }

        // Get grace period days from level
        global $wpdb;
        $grace_days = $wpdb->get_var($wpdb->prepare("
            SELECT grace_period_days 
            FROM {$this->levels_table}
            WHERE id = %d
        ", $membership->level_id));

        if (!$grace_days) {
            return false;
        }

        // Calculate grace period end date
        $grace_end = $this->calculateGracePeriodEnd($membership->end_date, $grace_days);

        return $this->update($id, [
            'status' => 'in_grace_period',
            'grace_period_end_date' => $grace_end
        ]);
    }

    /**
     * Extend membership period
     *
     * @param int $id Membership ID
     * @param int $months Number of months to extend
     * @return bool Success status
     */
    public function extendPeriod(int $id, int $months): bool {
        $membership = $this->find($id);
        if (!$membership) {
            return false;
        }

        // Calculate new end date
        $new_end_date = date('Y-m-d H:i:s', 
            strtotime($membership->end_date . ' +' . $months . ' months')
        );

        return $this->update($id, [
            'end_date' => $new_end_date,
            'period_months' => $membership->period_months + $months
        ]);
    }

    /**
     * Calculate membership end date
     *
     * @param string $start_date Start date (MySQL format)
     * @param int $months Number of months
     * @return string End date (MySQL format)
     */
    public function calculateEndDate(string $start_date, int $months): string {
        return date('Y-m-d H:i:s', 
            strtotime($start_date . ' +' . $months . ' months')
        );
    }

    /**
     * Calculate grace period end date
     *
     * @param string $end_date Membership end date
     * @param int $grace_days Number of grace period days
     * @return string Grace period end date
     */
    public function calculateGracePeriodEnd(string $end_date, int $grace_days): string {
        return date('Y-m-d H:i:s', 
            strtotime($end_date . ' +' . $grace_days . ' days')
        );
    }

    /**
     * Update payment status
     *
     * @param int $id Membership ID
     * @param string $status New payment status
     * @return bool Success status
     */
    public function updatePaymentStatus(int $id, string $status): bool {
        return $this->update($id, [
            'payment_status' => $status,
            'payment_date' => current_time('mysql')
        ]);
    }

    /**
     * Record payment for membership
     *
     * @param int $id Membership ID
     * @param array $payment_data Payment information
     * @return bool Success status
     */
    public function recordPayment(int $id, array $payment_data): bool {
        $data = wp_parse_args($payment_data, [
            'payment_status' => 'paid',
            'payment_date' => current_time('mysql')
        ]);

        return $this->update($id, $data);
    }

    /**
     * Upgrade membership to new level
     *
     * @param int $id Current membership ID
     * @param int $new_level_id New level ID
     * @return bool Success status
     */
    public function upgradeMembership(int $id, int $new_level_id): bool {
        $membership = $this->find($id);
        if (!$membership) {
            return false;
        }

        // Update level and status
        return $this->update($id, [
            'level_id' => $new_level_id,
            'status' => 'pending_payment'
        ]);
    }

    /**
     * Calculate upgrade price
     *
     * @param int $id Current membership ID
     * @param int $new_level_id New level ID
     * @return float|false Upgrade price or false on failure
     */
    public function calculateUpgradePrice(int $id, int $new_level_id) {
        $membership = $this->find($id);
        if (!$membership) {
            return false;
        }

        global $wpdb;
        
        // Get current and new level prices
        $prices = $wpdb->get_row($wpdb->prepare("
            SELECT 
                (SELECT price_per_month FROM {$this->levels_table} WHERE id = %d) as current_price,
                (SELECT price_per_month FROM {$this->levels_table} WHERE id = %d) as new_price
        ", $membership->level_id, $new_level_id));

        if (!$prices) {
            return false;
        }

        // Calculate remaining days in current period
        $remaining_days = (strtotime($membership->end_date) - time()) / (60 * 60 * 24);
        if ($remaining_days <= 0) {
            return $prices->new_price;
        }

        // Calculate prorated price difference
        $price_difference = $prices->new_price - $prices->current_price;
        $prorated_amount = ($price_difference / 30) * $remaining_days;

        return $prorated_amount;
    }

    /**
     * Check if customer can upgrade to target level
     *
     * @param int $company_id Customer ID
     * @param int $target_level_id Target level ID
     * @return bool Whether upgrade is possible
     */
    public function canUpgrade(int $company_id, int $target_level_id): bool {
        $current = $this->findByCustomer($company_id);
        if (!$current) {
            return false;
        }

        // Cannot upgrade to same or lower level
        global $wpdb;
        $current_sort_order = $wpdb->get_var($wpdb->prepare("
            SELECT sort_order FROM {$this->levels_table} WHERE id = %d
        ", $current->level_id));

        $target_sort_order = $wpdb->get_var($wpdb->prepare("
            SELECT sort_order FROM {$this->levels_table} WHERE id = %d
        ", $target_level_id));

        return $target_sort_order > $current_sort_order;
    }

    /**
     * Check if membership is active
     *
     * @param int $company_id Customer ID
     * @return bool Active status
     */
    public function isActive(int $company_id): bool {
        $membership = $this->findByCustomer($company_id);
        if (!$membership) {
            return false;
        }

        return $membership->status === 'active';
    }

    /**
     * Check if membership has expired
     *
     * @param int $company_id Customer ID
     * @return bool Expired status
     */
    public function hasExpired(int $company_id): bool {
        $membership = $this->findByCustomer($company_id);
        if (!$membership) {
            return true;
        }

        return strtotime($membership->end_date) < time();
    }

    /**
     * Check if membership is in grace period
     *
     * @param int $company_id Customer ID
     * @return bool Grace period status
     */
    public function inGracePeriod(int $company_id): bool {
        $membership = $this->findByCustomer($company_id);
        if (!$membership) {
            return false;
        }

        return $membership->status === 'in_grace_period' && 
               strtotime($membership->grace_period_end_date) > time();
    }

    /**
     * Clear membership cache
     *
     * @param int $id Membership ID
     */
    private function clearCache(int $id): void {
        $this->cache->delete('membership', $id);
        
        // Get customer ID to clear customer-specific cache
        global $wpdb;
        $company_id = $wpdb->get_var($wpdb->prepare("
            SELECT company_id FROM {$this->table} WHERE id = %d
        ", $id));

        if ($company_id) {
            $this->cache->delete('company_membership', $company_id);
        }
    }

    /**
     * Generate cache key
     *
     * @param string $type Cache type
     * @param int $id ID value
     * @return string Cache key
     */
    private function getCacheKey(string $type, int $id): string {
        return sprintf('%s_%d', $type, $id);
    }

    /**
     * Get membership status label
     * 
     * @param string $status Status code
     * @return string Status label in Indonesian
     */
    public function getStatusLabel(string $status): string {
        $labels = [
            'active' => __('Aktif', 'wp-customer'),
            'pending_payment' => __('Menunggu Pembayaran', 'wp-customer'),
            'pending_upgrade' => __('Proses Upgrade', 'wp-customer'),
            'expired' => __('Kadaluarsa', 'wp-customer'),
            'in_grace_period' => __('Masa Tenggang', 'wp-customer')
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get payment status label
     * 
     * @param string $status Payment status code
     * @return string Payment status label in Indonesian
     */
    public function getPaymentStatusLabel(string $status): string {
        $labels = [
            'paid' => __('Lunas', 'wp-customer'),
            'pending' => __('Belum Dibayar', 'wp-customer'),
            'failed' => __('Gagal', 'wp-customer'),
            'refunded' => __('Dikembalikan', 'wp-customer')
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get active employee count for a customer
     *
     * @param int $company_id Customer ID
     * @return int Number of active employees
     */
    public function getActiveEmployeeCount(int $company_id): int {
        // Check cache first
        $cached_count = $this->cache->get('customer_active_employee_count', $company_id);
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}app_customer_employees
            WHERE branch_id = %d 
            AND status = 'active'
        ", $company_id));

        // Cache for 5 minutes since employee count can change frequently
        $this->cache->set('customer_active_employee_count', $count, 300, $company_id);

        return $count;
    }

}
