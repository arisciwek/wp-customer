<?php
/**
 * Agency Company Filter
 *
 * @package     WP_Customer
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Integrations/AgencyCompanyFilter.php
 *
 * Description: Filter company (branches) list untuk agency users.
 *              Agency users hanya bisa melihat companies yang berada
 *              di province yang sama dengan agency mereka.
 *
 * Filter Logic:
 * - Agency user → Get agency's province_id
 * - Filter companies: hanya yang province_id sama
 * - Alternatif (lebih luas): filter by agency_id (semua company yang assign ke agency)
 *
 * Integration Pattern:
 * - Hooks into CompanyDataTableModel WHERE filter
 * - Works alongside other access filters
 * - No tight coupling between plugins
 *
 * Dependencies:
 * - wp-agency plugin (provides agency roles & data)
 * - wp_app_agencies table (agency → province mapping)
 * - wp_app_customer_branches table (company/branch data)
 *
 * Changelog:
 * 1.0.0 - 2025-12-26
 * - Initial implementation
 * - Province-based filtering for agency users
 * - Hook into wpapp_datatable_company_where
 */

namespace WPCustomer\Integrations;

class AgencyCompanyFilter {

    /**
     * Constructor
     * Register filter hooks
     */
    public function __construct() {
        // Hook into company DataTable WHERE filter
        // Priority 20: run after DataTableAccessFilter (priority 10)
        add_filter('wpapp_datatable_company_where', [$this, 'filter_where_conditions'], 20, 3);
    }

    /**
     * Filter WHERE conditions for company datatable
     *
     * Province-based filtering: agency users only see companies in their province.
     * Alternative (broader): filter by agency_id (companies assigned to the agency).
     *
     * Hooked to: wpapp_datatable_company_where
     *
     * @param array $where_conditions Current WHERE conditions (array of SQL strings)
     * @param array $request_data DataTables request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function filter_where_conditions($where_conditions, $request_data, $model) {
        error_log('=== AgencyCompanyFilter::filter_where_conditions CALLED ===');
        error_log('User ID: ' . get_current_user_id());
        error_log('Where conditions count: ' . count($where_conditions));

        // Check if admin (no filtering)
        if (current_user_can('manage_options')) {
            error_log('User is admin - skipping filter');
            return $where_conditions;
        }

        // Check if user has agency role
        if (!$this->hasAgencyRole()) {
            error_log('User does not have agency role - skipping filter');
            return $where_conditions;
        }

        error_log('User has agency role - applying filter');

        // Get user's agency province_id
        $province_id = $this->getUserAgencyProvinceId();
        error_log('Agency Province ID: ' . ($province_id ?? 'NULL'));

        if (!$province_id) {
            error_log('No province_id - blocking all results');
            // No province assigned - block all results
            $where_conditions[] = '1=0';
            return $where_conditions;
        }

        // Add province filter to WHERE conditions
        // Filter companies by province (same province as agency)
        global $wpdb;
        $alias = method_exists($model, 'get_table_alias') ? $model->get_table_alias() : 'cc';

        error_log('Adding province_id WHERE condition: ' . $province_id);
        error_log('Using table alias: ' . $alias);

        $where_conditions[] = sprintf(
            "{$alias}.province_id = %d",
            intval($province_id)
        );

        error_log('=== END AgencyCompanyFilter::filter_where_conditions ===');
        return $where_conditions;
    }

    /**
     * Check if current user has agency plugin role
     *
     * Uses WP_Agency_Role_Manager for role list if available.
     *
     * @return bool True if user has agency role
     */
    private function hasAgencyRole() {
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // Get agency roles from WP_Agency_Role_Manager if available
        if (class_exists('WP_Agency_Role_Manager')) {
            $agency_roles = \WP_Agency_Role_Manager::getRoleSlugs();
        } else {
            // Fallback to hardcoded list if wp-agency not available
            $agency_roles = [
                'agency',
                'agency_employee',
                'agency_admin_dinas',
                'agency_admin_unit',
                'agency_pengawas',
                'agency_pengawas_spesialis',
                'agency_kepala_unit',
                'agency_kepala_seksi',
                'agency_kepala_bidang',
                'agency_kepala_dinas'
            ];
        }

        foreach ($user_roles as $role) {
            if (in_array($role, $agency_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get agency's province_id from user
     *
     * Logic:
     * 1. Get user's agency_id (from agency admin OR employee table)
     * 2. Get agency's province_id
     *
     * @return int|null Province ID or null if not found
     */
    private function getUserAgencyProvinceId() {
        global $wpdb;
        static $cache = [];

        $user_id = get_current_user_id();

        error_log('getUserAgencyProvinceId() - User ID: ' . $user_id);

        // Check cache
        if (isset($cache[$user_id])) {
            error_log('getUserAgencyProvinceId() - Cache hit: ' . $cache[$user_id]);
            return $cache[$user_id];
        }

        // Get agency_id first
        $agency_id = $this->getUserAgencyId();

        if (!$agency_id) {
            error_log('getUserAgencyProvinceId() - No agency_id found');
            $cache[$user_id] = null;
            return null;
        }

        error_log('getUserAgencyProvinceId() - Agency ID: ' . $agency_id);

        // Get province_id from agency
        $province_id = $wpdb->get_var(sprintf("
            SELECT province_id
            FROM {$wpdb->prefix}app_agencies
            WHERE id = %d
            LIMIT 1
        ", intval($agency_id)));

        error_log('getUserAgencyProvinceId() - Province ID: ' . ($province_id ?? 'NULL'));

        $cache[$user_id] = $province_id ? (int) $province_id : null;
        return $cache[$user_id];
    }

    /**
     * Get agency_id from user (agency admin OR employee)
     *
     * Logic (same as AgencyCustomerFilter):
     * 1. Check if user is agency admin (wp_app_agencies.user_id)
     * 2. If not, check if user is agency employee (wp_app_agency_employees.agency_id)
     *
     * @return int|null Agency ID or null if not found
     */
    private function getUserAgencyId() {
        global $wpdb;
        static $cache = [];

        $user_id = get_current_user_id();

        error_log('getUserAgencyId() - User ID: ' . $user_id);

        // Check cache
        if (isset($cache[$user_id])) {
            error_log('getUserAgencyId() - Cache hit: ' . $cache[$user_id]);
            return $cache[$user_id];
        }

        // Check 1: Is user an agency admin?
        $agency_id = $wpdb->get_var(sprintf("
            SELECT id
            FROM {$wpdb->prefix}app_agencies
            WHERE user_id = %d
            LIMIT 1
        ", intval($user_id)));

        error_log('getUserAgencyId() - Check agency admin: ' . ($agency_id ?? 'NULL'));

        if ($agency_id) {
            $cache[$user_id] = (int) $agency_id;
            error_log('getUserAgencyId() - Found as admin, agency_id: ' . $cache[$user_id]);
            return $cache[$user_id];
        }

        // Check 2: Is user an agency employee?
        $agency_id = $wpdb->get_var(sprintf("
            SELECT agency_id
            FROM {$wpdb->prefix}app_agency_employees
            WHERE user_id = %d
            LIMIT 1
        ", intval($user_id)));

        error_log('getUserAgencyId() - Check agency employee: ' . ($agency_id ?? 'NULL'));

        $cache[$user_id] = $agency_id ? (int) $agency_id : null;
        error_log('getUserAgencyId() - Final result: ' . ($cache[$user_id] ?? 'NULL'));
        return $cache[$user_id];
    }

    /**
     * Get filter statistics (for debugging/logging)
     *
     * @return array Statistics
     */
    public function getFilterStats() {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $has_agency_role = $this->hasAgencyRole();
        $province_id = $this->getUserAgencyProvinceId();

        return [
            'user_id' => $current_user_id,
            'is_admin' => $is_admin,
            'has_agency_role' => $has_agency_role,
            'province_id' => $province_id,
            'filter_active' => !$is_admin && $has_agency_role && $province_id !== null
        ];
    }
}
