<?php
/**
 * Agency Integration
 *
 * Integrates wp-customer with wp-agency plugin.
 * Provides customer statistics and access control for agencies.
 *
 * @package WPCustomer\Controllers\Integration\Integrations
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration\Integrations;

defined('ABSPATH') || exit;

/**
 * AgencyIntegration Class
 *
 * Configuration-based integration for wp-agency plugin.
 *
 * @since 1.0.12
 */
class AgencyIntegration implements EntityIntegrationInterface {

    /**
     * Initialize the integration
     *
     * Register filter hooks for entity configurations.
     *
     * @return void
     * @since 1.0.12
     */
    public function init(): void {
        // Register entity relation configuration
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config'], 10, 1);

        // Register tab injection configuration
        add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_injection_config'], 10, 1);

        // Register DataTable access configuration
        add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config'], 10, 1);
    }

    /**
     * Get entity type identifier
     *
     * @return string Entity type
     * @since 1.0.12
     */
    public function get_entity_type(): string {
        return 'agency';
    }

    /**
     * Check if integration should load
     *
     * Checks if wp-agency plugin is active.
     *
     * @return bool True if should load
     * @since 1.0.12
     */
    public function should_load(): bool {
        // Check if wp-agency plugin is active
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if wp-agency is active
        $is_active = is_plugin_active('wp-agency/wp-agency.php');

        /**
         * Filter: Override agency integration loading
         *
         * @param bool $is_active Whether wp-agency is active
         * @return bool Modified is_active
         *
         * @since 1.0.12
         */
        return apply_filters('wp_customer_agency_integration_should_load', $is_active);
    }

    /**
     * Register entity relation configuration
     *
     * Defines how customers relate to agencies via branches.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     *
     * @since 1.0.12
     */
    public function register_relation_config(array $configs): array {
        $configs['agency'] = [
            // Bridge table connecting customers to agencies
            'bridge_table' => 'app_customer_branches',

            // Column in bridge table that references agency
            'entity_column' => 'agency_id',

            // Column in bridge table that references customer
            'customer_column' => 'customer_id',

            // Enable user access filtering
            'access_filter' => true,

            // Cache TTL (1 hour)
            'cache_ttl' => 3600,

            // Cache group
            'cache_group' => 'wp_customer_agency_relations'
        ];

        /**
         * Filter: Modify agency relation configuration
         *
         * @param array $config Agency relation configuration
         * @return array Modified configuration
         *
         * @since 1.0.12
         */
        $configs['agency'] = apply_filters('wp_customer_agency_relation_config', $configs['agency']);

        return $configs;
    }

    /**
     * Register tab injection configuration
     *
     * Defines how customer statistics appear in agency tabs.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     *
     * @since 1.0.12
     */
    public function register_tab_injection_config(array $configs): array {
        $configs['agency'] = [
            // Which tabs to inject into
            'tabs' => ['info', 'details'],

            // Template to use
            'template' => 'statistics-simple',

            // Section label
            'label' => __('Statistik Customer', 'wp-customer'),

            // Position hint (for future use)
            'position' => 'after_metadata',

            // Hook priority
            'priority' => 20
        ];

        /**
         * Filter: Modify agency tab injection configuration
         *
         * @param array $config Agency tab injection configuration
         * @return array Modified configuration
         *
         * @since 1.0.12
         */
        $configs['agency'] = apply_filters('wp_customer_agency_tab_injection_config', $configs['agency']);

        return $configs;
    }

    /**
     * Register DataTable access configuration
     *
     * Defines access control for agency DataTable.
     *
     * @param array $configs Existing configurations
     * @return array Modified configurations
     *
     * @since 1.0.12
     */
    public function register_access_config(array $configs): array {
        $configs['agency'] = [
            // Filter hook name
            'hook' => 'wpapp_datatable_agencies_where',

            // Table alias used in DataTable query
            'table_alias' => 'a',

            // ID column name
            'id_column' => 'id',

            // Hook priority
            'priority' => 10
        ];

        /**
         * Filter: Modify agency access configuration
         *
         * @param array $config Agency access configuration
         * @return array Modified configuration
         *
         * @since 1.0.12
         */
        $configs['agency'] = apply_filters('wp_customer_agency_access_config', $configs['agency']);

        return $configs;
    }
}
