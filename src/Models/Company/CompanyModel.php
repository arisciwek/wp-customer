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
use WPCustomer\Models\Customer\CustomerModel;

class CompanyModel {
    private $table;
    private $customer_table;
    private $memberships_table;
    private $levels_table;
    private CustomerCacheManager $cache;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches';
        $this->memberships_table = $wpdb->prefix . 'app_customer_memberships';
        $this->levels_table = $wpdb->prefix . 'app_customer_membership_levels';
        $this->cache = new CustomerCacheManager();
        $this->customer_table = new CustomerModel();
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
                   c.name as customer_name,
                   a.name as agency_name,
                   d.name as division_name,
                   u.display_name as inspector_name
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
            LEFT JOIN {$wpdb->prefix}app_agencies a ON b.agency_id = a.id
            LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON b.division_id = d.id
            LEFT JOIN {$wpdb->users} u ON b.inspector_id = u.ID
            WHERE b.id = %d
        ", $id));

        // Cache the result for 2 minutes
        if ($result) {
            $this->cache->set('branch_membership', $result, 120, $id);
        }

        return $result;
    }

    public function getDataTableData(int $start, int $length, string $search, string $orderColumn, string $orderDir, int $filterAktif = 1, int $filterTidakAktif = 0): array {
        // Dapatkan access_type dari validator
        global $wp_branch_validator;
        if (!$wp_branch_validator) {
            $wp_branch_validator = new \WPCustomer\Validators\Branch\BranchValidator();
        }
        $access = $wp_branch_validator->validateAccess(0);
        $access_type = $access['access_type'];
        
        // Check cache first
        $cached_result = $this->cache->getDataTableCache(
            'company_list',
            $access_type,
            $start,
            $length,
            $search,
            $orderColumn,
            strtolower($orderDir),
            ['filter_aktif' => $filterAktif, 'filter_tidak_aktif' => $filterTidakAktif]
        );

        if ($cached_result) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Model cache hit for DataTable - Key: company_list_{$access_type}");
            }
            return $cached_result;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Model cache miss for DataTable - Key: company_list_{$access_type}");
        }
        
        // Base query parts
        global $wpdb;
        
        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS b.*,
                         m.level_id,
                         l.name as level_name,
                         c.name as customer_name,
                         a.name as agency_name,
                         d.name as division_name,
                         u.display_name as inspector_name";
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
                LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                LEFT JOIN {$wpdb->prefix}app_agencies a ON b.agency_id = a.id
                LEFT JOIN {$wpdb->prefix}app_agency_divisions d ON b.division_id = d.id
                LEFT JOIN {$wpdb->users} u ON b.inspector_id = u.ID";
        $where = " WHERE 1=1";
        $where_params = [];

        // Get user relation for access filtering
        $customerModel = new CustomerModel();
        $relation = $customerModel->getUserRelation(0);

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all companies
            error_log('CompanyModel DataTable: User is admin - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all companies under their customer
            $where .= " AND c.user_id = %d";
            $where_params[] = get_current_user_id();
            error_log('CompanyModel DataTable: Added customer admin restriction');
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see their own company/branch
            $where .= " AND b.user_id = %d";
            $where_params[] = get_current_user_id();
            error_log('CompanyModel DataTable: Added customer branch admin restriction');
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see the company/branch they work in
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where .= " AND b.id = %d";
                $where_params[] = $employee_branch;
                error_log('CompanyModel DataTable: Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('CompanyModel DataTable: Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('CompanyModel DataTable: User has no access - blocking all');
        }

        // Apply filter for extensibility (allow other plugins to modify WHERE clause)
        $where = apply_filters('wp_company_datatable_where', $where, $access_type, $relation, $where_params);

        // Add search if provided
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND (b.code LIKE %s OR b.name LIKE %s OR b.type LIKE %s OR l.name LIKE %s OR a.name LIKE %s OR d.name LIKE %s OR u.display_name LIKE %s)";
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
        }

        // Add filter conditions
        $filter_conditions = [];
        if ($filterAktif && !$filterTidakAktif) {
            $filter_conditions[] = "m.status = 'active'";
        } elseif (!$filterAktif && $filterTidakAktif) {
            $filter_conditions[] = "(m.status != 'active' OR m.status IS NULL)";
        } elseif (!$filterAktif && !$filterTidakAktif) {
            $filter_conditions[] = "1=0"; // No results if no filter selected
        }
        if (!empty($filter_conditions)) {
            $where .= " AND " . implode(" AND ", $filter_conditions);
        }

        // Validate order column
        $validColumns = ['code', 'name', 'type', 'level_name', 'agency_name', 'division_name', 'inspector_name'];
        if (!in_array($orderColumn, $validColumns)) {
            $orderColumn = 'code';
        }

        // Add order
        $orderDir = strtoupper($orderDir) === 'desc' ? 'DESC' : 'ASC';
        $order = " ORDER BY " . esc_sql($orderColumn) . " " . esc_sql($orderDir);

        // Add limit
        $limit = " LIMIT %d, %d";
        $where_params[] = $start;
        $where_params[] = $length;

        // Complete query
        $sql = $select . $from . $join . $where . $order . $limit;

        // Start timing
        $start_time = microtime(true);

        // Prepare and execute query
        if (!empty($where_params)) {
            $prepared_sql = $wpdb->prepare($sql, $where_params);
            $results = $wpdb->get_results($prepared_sql);
        } else {
            $results = $wpdb->get_results($sql);
        }
        
        // Calculate execution time
        $execution_time = microtime(true) - $start_time;
        
        // Debug SQL Query - Print RAW query yang sebenarnya dieksekusi
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=================================================================");
            error_log("DEBUG SQL QUERY - getDataTableData() - " . date('Y-m-d H:i:s'));
            error_log("=================================================================");
            error_log("INPUT PARAMETERS:");
            error_log("  - Start: " . $start);
            error_log("  - Length: " . $length);
            error_log("  - Search: " . ($search ?: '(empty)'));
            error_log("  - Order Column: " . $orderColumn);
            error_log("  - Order Direction: " . $orderDir);
            error_log("  - Access Type: " . $access_type);
            error_log("-----------------------------------------------------------------");
            error_log("RAW SQL QUERY (Actual Executed):");
            error_log($wpdb->last_query);
            error_log("-----------------------------------------------------------------");
        }
        
        if ($results === null) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ERROR: Query failed!");
                error_log("MySQL Error: " . $wpdb->last_error);
                error_log("=================================================================");
            }
            throw new \Exception($wpdb->last_error);
        }

        // Get total filtered count
        $filtered = $wpdb->get_var("SELECT FOUND_ROWS()");

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
        
        // Debug results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("QUERY RESULTS:");
            error_log("  - Execution Time: " . number_format($execution_time, 4) . " seconds");
            error_log("  - Rows Returned: " . count($results));
            error_log("  - Total Records: " . $total);
            error_log("  - Filtered Records: " . $filtered);
            error_log("  - Cache Status: MISS (new query executed)");
            error_log("-----------------------------------------------------------------");
            error_log("FOUND_ROWS Query:");
            error_log($wpdb->last_query);
            error_log("-----------------------------------------------------------------");
            error_log("COUNT Query:");
            // Get count query
            $count_query = $wpdb->last_query;
            error_log($count_query);
            error_log("=================================================================");
            error_log("");
        }

        // Prepare result
        $result = [
            'data' => $results,
            'total' => (int) $total,
            'filtered' => (int) $filtered
        ];
        
        // Set cache
        $this->cache->setDataTableCache(
            'company_list',
            $access_type,
            $start,
            $length,
            $search,
            $orderColumn,
            strtolower($orderDir),
            $result,
            ['filter_aktif' => $filterAktif, 'filter_tidak_aktif' => $filterTidakAktif]
        );
        
        return $result;
    }

    /**
     * Get total count based on user permission with access_type filtering
     */
    public function getTotalCount(): int {
        global $wpdb;

        error_log('=== Debug CompanyModel getTotalCount ===');
        error_log('User ID: ' . get_current_user_id());

        // Get user relation from CustomerModel to determine access
        $customerModel = new CustomerModel();
        $relation = $customerModel->getUserRelation(0);
        $access_type = $relation['access_type'];

        error_log('Access type: ' . $access_type);
        error_log('Is admin: ' . ($relation['is_admin'] ? 'yes' : 'no'));
        error_log('Is customer admin: ' . ($relation['is_customer_admin'] ? 'yes' : 'no'));
        error_log('Is customer branch admin: ' . ($relation['is_customer_branch_admin'] ? 'yes' : 'no'));
        error_log('Is employee: ' . ($relation['is_customer_employee'] ? 'yes' : 'no'));

        // Base query parts
        $select = "SELECT SQL_CALC_FOUND_ROWS r.*, p.name as customer_name";
        $from = " FROM {$this->table} r";
        $join = " LEFT JOIN {$wpdb->prefix}app_customers p ON r.customer_id = p.id";

        // Default where clause
        $where = " WHERE 1=1";
        $params = [];

        // Debug query building process
        error_log('Building WHERE clause:');
        error_log('Initial WHERE: ' . $where);

        // Apply filtering based on access type
        if ($relation['is_admin']) {
            // Administrator - see all companies
            error_log('User is admin - no additional restrictions');
        }
        elseif ($relation['is_customer_admin']) {
            // Customer Admin - see all companies under their customer
            $where .= " AND p.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added customer admin restriction: ' . $where);
        }
        elseif ($relation['is_customer_branch_admin']) {
            // Customer Branch Admin - only see their own company/branch
            $where .= " AND r.user_id = %d";
            $params[] = get_current_user_id();
            error_log('Added customer branch admin restriction - only own branch');
        }
        elseif ($relation['is_customer_employee']) {
            // Employee - only see the company/branch they work in
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                get_current_user_id()
            ));

            if ($employee_branch) {
                $where .= " AND r.id = %d";
                $params[] = $employee_branch;
                error_log('Added employee restriction for branch: ' . $employee_branch);
            } else {
                $where .= " AND 1=0"; // No branch found
                error_log('Employee has no branch - blocking access');
            }
        }
        else {
            // No access
            $where .= " AND 1=0";
            error_log('User has no access - blocking all');
        }

        // Apply filter for extensibility
        $where = apply_filters('wp_company_total_count_where', $where, $access_type, $relation, $params);

        // Complete query
        $query = $select . $from . $join . $where;
        $final_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        error_log('Final Query: ' . $final_query);

        // Execute query
        $wpdb->get_results($final_query);

        // Get total and log
        $total = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
        error_log('Total count result: ' . $total);
        error_log('=== End Debug ===');

        return $total;
    }
}
