<?php
/**
 * Agency Access Capability Filter
 *
 * Grants customer employees access to wp-agency menu and pages
 * by dynamically adding 'view_agency_list' capability.
 *
 * Access is filtered at database level by DataTableAccessFilter,
 * so customer employees only see agencies they have access to.
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration;

defined('ABSPATH') || exit;

/**
 * AgencyAccessCapabilityFilter Class
 *
 * Provides dynamic capability injection for customer roles
 * to access wp-agency menu while maintaining security through
 * database-level filtering.
 *
 * @since 1.0.12
 */
class AgencyAccessCapabilityFilter {

    /**
     * Customer roles that should have agency access
     *
     * @var array
     */
    private $customer_roles = [
        'customer_admin',
        'customer_branch_admin',
        'customer_employee'
    ];

    /**
     * Agency capabilities to grant
     *
     * @var array
     */
    private $agency_capabilities = [
        'view_agency_list',      // Access to agency list menu/page
        'view_agency_detail',    // Access to agency detail page
    ];

    /**
     * Constructor
     *
     * @since 1.0.12
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.12
     */
    private function init_hooks(): void {
        // Filter user capabilities to add agency access
        add_filter('user_has_cap', [$this, 'grant_agency_access'], 10, 4);

        /**
         * Action: After agency access capability filter initialized
         *
         * @param AgencyAccessCapabilityFilter $filter Filter instance
         *
         * @since 1.0.12
         */
        do_action('wp_customer_agency_access_capability_filter_init', $this);
    }

    /**
     * Grant agency access to customer roles
     *
     * Dynamically adds agency view capabilities to customer roles
     * so they can access wp-agency menu and pages.
     *
     * Database-level filtering (via DataTableAccessFilter) ensures
     * they only see agencies they have access to.
     *
     * @param array   $allcaps All capabilities of the user
     * @param array   $caps    Required capabilities for the request
     * @param array   $args    Arguments (capability, user_id, object_id)
     * @param WP_User $user    User object
     * @return array Modified capabilities
     *
     * @since 1.0.12
     */
    public function grant_agency_access(array $allcaps, array $caps, array $args, $user): array {
        // Check if user has any customer role
        $has_customer_role = false;
        foreach ($this->customer_roles as $role) {
            if (in_array($role, $user->roles)) {
                $has_customer_role = true;
                break;
            }
        }

        if (!$has_customer_role) {
            return $allcaps;
        }

        /**
         * Filter: Check if customer role should have agency access
         *
         * @param bool    $should_grant Whether to grant access
         * @param WP_User $user         User object
         * @param array   $allcaps      All capabilities
         * @return bool Modified should_grant
         *
         * @since 1.0.12
         */
        $should_grant = apply_filters('wp_customer_should_grant_agency_access', true, $user, $allcaps);

        if (!$should_grant) {
            return $allcaps;
        }

        // Grant agency view capabilities
        foreach ($this->agency_capabilities as $cap) {
            $allcaps[$cap] = true;
        }

        /**
         * Filter: Modify granted capabilities
         *
         * @param array   $allcaps All capabilities (with agency caps added)
         * @param WP_User $user    User object
         * @return array Modified capabilities
         *
         * @since 1.0.12
         */
        return apply_filters('wp_customer_granted_agency_capabilities', $allcaps, $user);
    }

    /**
     * Get customer roles that have agency access
     *
     * @return array Customer role slugs
     * @since 1.0.12
     */
    public function get_customer_roles(): array {
        return $this->customer_roles;
    }

    /**
     * Get agency capabilities that are granted
     *
     * @return array Agency capability slugs
     * @since 1.0.12
     */
    public function get_agency_capabilities(): array {
        return $this->agency_capabilities;
    }

    /**
     * Check if user should have agency access
     *
     * @param int|WP_User $user User ID or object
     * @return bool True if should have access
     * @since 1.0.12
     */
    public function should_have_agency_access($user): bool {
        if (is_int($user)) {
            $user = get_user_by('id', $user);
        }

        if (!$user) {
            return false;
        }

        foreach ($this->customer_roles as $role) {
            if (in_array($role, $user->roles)) {
                return true;
            }
        }

        return false;
    }
}
