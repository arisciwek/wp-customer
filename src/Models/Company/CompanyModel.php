<?php

/**
 * Company Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Company/CompanyModel.php
 *
 * Description: Model untuk menampilkan data perusahaan.
 *              Handles operasi view dengan caching terintegrasi.
 *              Includes query optimization dan data formatting.
 *              Menyediakan metode untuk DataTables server-side.
 *
 * Changelog:
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added view operations
 * - Added cache integration
 * - Added membership data integration
 */

namespace WPCustomer\Models\Company;

use WPCustomer\Cache\CustomerCacheManager;

class CompanyModel {
    private $table;
    private $memberships_table;
    private $levels_table;
    private CustomerCacheManager $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_branches';
        $this->memberships_table = $wpdb->prefix . 'app_customer_memberships';
        $this->levels_table = $wpdb->prefix . 'app_customer_membership_levels';
        $this->cache = new CustomerCacheManager();
    }

    /**
     * Get company with latest membership data
     */
    public function getBranchWithLatestMembership($id) {
        // Check cache first
        $cached_result = $this->cache->get('branch_membership', $id);
        if ($cached_result !== null) {
            return $cached_result;
        }

        // If no cache, do the complex query
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, 
                   m.level_id,
                   m.start_date as membership_start,
                   m.end_date as membership_end,
                   m.status as membership_status,
                   l.name as level_name,
                   c.name as customer_name
            FROM {$this->table} b
            LEFT JOIN (
                SELECT m1.*
                FROM {$this->memberships_table} m1
                LEFT JOIN {$this->memberships_table} m2
                ON m1.branch_id = m2.branch_id 
                AND m1.created_at < m2.created_at
                WHERE m2.branch_id IS NULL
            ) m ON b.id = m.branch_id
            LEFT JOIN {$this->levels_table} l ON m.level_id = l.id
            LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
            WHERE b.id = %d
        ", $id));

        // Cache the result for 2 minutes
        if ($result) {
            $this->cache->set('branch_membership', $result, 120, $id);
        }

        return $result;
    }

    /**
     * Get data for DataTables server-side processing
     */
    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir): array {
        global $wpdb;

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS b.*, 
                         m.level_id,
                         l.name as level_name,
                         c.name as customer_name";
        $from = " FROM {$this->table} b";
        $join = " LEFT JOIN (
                    SELECT m1.*
                    FROM {$this->memberships_table} m1
                    LEFT JOIN {$this->memberships_table} m2
                    ON m1.branch_id = m2.branch_id 
                    AND m1.created_at < m2.created_at
                    WHERE m2.branch_id IS NULL
                ) m ON b.id = m.branch_id
                LEFT JOIN {$this->levels_table} l ON m.level_id = l.id
                LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id";
        $where = " WHERE 1=1";

        // Add search if provided
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (b.name LIKE %s OR b.code LIKE %s OR c.name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Validate order column
        $validColumns = ['code', 'name', 'type', 'customer_name', 'level_name'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Add order
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = $wpdb->prepare(" LIMIT %d, %d", $start, $length);

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Get paginated results
        $results = $wpdb->get_results($sql);
        if ($results === null) {
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        return [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
    }

    /**
     * Get total count based on user permission
     */
    public function getTotalCount(): int {
        $cached_count = $this->cache->get('company_total_count', get_current_user_id());
        if ($cached_count !== null) {
            return (int) $cached_count;
        }

        global $wpdb;
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        $this->cache->set('company_total_count', $count, 120, get_current_user_id());

        return $count;
    }
}
