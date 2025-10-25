<?php
/**
 * EXAMPLE: Inspector Companies Access Integration
 *
 * This is a REFERENCE implementation showing how inspector access can be
 * implemented for the Companies (Branches) system using hooks.
 *
 * ⚠️  THIS FILE IS DISABLED BY DEFAULT - FOR REFERENCE ONLY ⚠️
 *
 * To implement in production:
 * 1. Copy relevant parts to your integration plugin (wp-agency or wp-inspector)
 * 2. Update namespace accordingly
 * 3. Initialize in main plugin file
 * 4. Test thoroughly before deploying
 *
 * @package WPCustomer
 * @subpackage Examples\Hooks
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Examples\Hooks;

use WPCustomer\Cache\CustomerCacheManager;

// IMPORTANT: This file is not autoloaded by default
// It exists purely as documentation/reference
if (!defined('WP_CUSTOMER_ENABLE_EXAMPLES')) {
    return; // Exit if examples are not explicitly enabled
}

/**
 * InspectorCompaniesAccess class
 *
 * Demonstrates how inspectors can be granted access to their assigned companies.
 *
 * Business Rules Implemented:
 * 1. Inspectors can view companies list page
 * 2. Inspectors can only view companies assigned to them
 * 3. Inspectors can edit certain fields in their assigned companies
 * 4. Inspectors cannot create or delete companies
 * 5. All checks are cached for performance
 *
 * @since 1.1.0
 */
class InspectorCompaniesAccess {

    /**
     * Cache manager instance
     *
     * @var CustomerCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache = new CustomerCacheManager();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Page access filter
        add_filter('wp_customer_can_access_companies_page', [$this, 'can_access_page'], 10, 2);

        // Permission filters
        add_filter('wp_customer_can_view_company', [$this, 'can_view_company'], 10, 2);
        add_filter('wp_customer_can_edit_company', [$this, 'can_edit_company'], 10, 2);

        // DataTable filter to show only assigned companies
        add_filter('wpapp_datatable_customer_branches_where', [$this, 'filter_datatable_query'], 10, 3);

        // Cache invalidation
        add_action('wp_customer_company_updated', [$this, 'clear_assignment_cache'], 10, 3);
    }

    /**
     * Filter: Allow inspectors to access companies page
     *
     * @param bool $can_access Default access permission
     * @param array $context Context data (user_id, is_admin)
     * @return bool
     */
    public function can_access_page($can_access, $context) {
        // If already has access, return early
        if ($can_access) {
            return $can_access;
        }

        $user_id = $context['user_id'];

        // Check if user is an inspector with assignments
        return $this->is_inspector_with_assignments($user_id);
    }

    /**
     * Filter: Allow inspectors to view their assigned companies
     *
     * @param bool $can_view Default view permission
     * @param int|null $company_id Company ID
     * @return bool
     */
    public function can_view_company($can_view, $company_id) {
        // If already has permission or no specific company, return early
        if ($can_view || !$company_id) {
            return $can_view;
        }

        $user_id = get_current_user_id();

        // Check if user is assigned as inspector to this company
        return $this->is_assigned_inspector($user_id, $company_id);
    }

    /**
     * Filter: Allow inspectors to edit certain fields in assigned companies
     *
     * @param bool $can_edit Default edit permission
     * @param int $company_id Company ID
     * @return bool
     */
    public function can_edit_company($can_edit, $company_id) {
        // If already has permission, return early
        if ($can_edit) {
            return $can_edit;
        }

        $user_id = get_current_user_id();

        // Check if user is assigned as inspector to this company
        // Note: You might want to implement field-level permissions separately
        return $this->is_assigned_inspector($user_id, $company_id);
    }

    /**
     * Filter: Modify DataTable query to show only assigned companies
     *
     * @param array $where WHERE conditions
     * @param array $request_data Request data from DataTable
     * @param object $model DataTable model instance
     * @return array Modified WHERE conditions
     */
    public function filter_datatable_query($where, $request_data, $model) {
        $user_id = get_current_user_id();

        // Skip filter if user has full access
        if (current_user_can('view_all_customer_branches')) {
            return $where;
        }

        // Check if user is inspector
        if (!$this->is_inspector($user_id)) {
            return $where;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'app_customer_branches';

        // Add WHERE condition to filter by inspector assignment
        $where[] = $wpdb->prepare(
            "{$table}.inspector_id = %d",
            $user_id
        );

        return $where;
    }

    /**
     * Check if user is an inspector (has inspector role/capability)
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_inspector($user_id) {
        // Check cache first
        $cache_key = "is_inspector_{$user_id}";
        $cached = $this->cache->get('inspector_access', $cache_key);

        if ($cached !== null) {
            return (bool) $cached;
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        // Check if user has inspector role or capability
        // Adjust this based on your inspector implementation
        $is_inspector = (
            in_array('inspector', $user->roles) ||
            user_can($user_id, 'inspect_companies')
        );

        // Cache for 15 minutes
        $this->cache->set('inspector_access', $is_inspector, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $is_inspector;
    }

    /**
     * Check if user is inspector with at least one company assignment
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_inspector_with_assignments($user_id) {
        // First check if user is an inspector
        if (!$this->is_inspector($user_id)) {
            return false;
        }

        // Check cache first
        $cache_key = "inspector_assignments_{$user_id}";
        $cached = $this->cache->get('inspector_assignments', $cache_key);

        if ($cached !== null) {
            return (bool) $cached;
        }

        global $wpdb;

        // Check if inspector has any company assignments
        $has_assignments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
             WHERE inspector_id = %d AND status = 'active'",
            $user_id
        ));

        $result = $has_assignments > 0;

        // Cache for 15 minutes
        $this->cache->set('inspector_assignments', $result, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $result;
    }

    /**
     * Check if user is assigned as inspector to specific company
     *
     * @param int $user_id User ID
     * @param int $company_id Company ID
     * @return bool
     */
    private function is_assigned_inspector($user_id, $company_id) {
        // Check cache first
        $cache_key = "inspector_assigned_{$user_id}_{$company_id}";
        $cached = $this->cache->get('inspector_company_access', $cache_key);

        if ($cached !== null) {
            return (bool) $cached;
        }

        global $wpdb;

        // Check if user is assigned as inspector to this company
        $is_assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
             WHERE id = %d AND inspector_id = %d",
            $company_id,
            $user_id
        ));

        $result = $is_assigned > 0;

        // Cache for 15 minutes
        $this->cache->set('inspector_company_access', $result, 15 * MINUTE_IN_SECONDS, $cache_key);

        return $result;
    }

    /**
     * Clear inspector assignment cache when company is updated
     *
     * @param int $company_id Company ID
     * @param object $old_data Old company data
     * @param object $new_data New company data
     */
    public function clear_assignment_cache($company_id, $old_data, $new_data) {
        // If inspector assignment changed, clear relevant caches
        if ($old_data->inspector_id !== $new_data->inspector_id) {
            // Clear cache for old inspector
            if ($old_data->inspector_id) {
                $this->cache->delete('inspector_assignments', "inspector_assignments_{$old_data->inspector_id}");
                $this->cache->delete('inspector_company_access', "inspector_assigned_{$old_data->inspector_id}_{$company_id}");
            }

            // Clear cache for new inspector
            if ($new_data->inspector_id) {
                $this->cache->delete('inspector_assignments', "inspector_assignments_{$new_data->inspector_id}");
                $this->cache->delete('inspector_company_access', "inspector_assigned_{$new_data->inspector_id}_{$company_id}");
            }
        }
    }
}

/**
 * Initialize the integration
 *
 * PRODUCTION: This should be called from your integration plugin
 */
if (defined('WP_CUSTOMER_ENABLE_EXAMPLES') && WP_CUSTOMER_ENABLE_EXAMPLES) {
    new InspectorCompaniesAccess();
}
