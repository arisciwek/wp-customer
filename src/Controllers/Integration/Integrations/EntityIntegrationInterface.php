<?php
/**
 * Entity Integration Interface
 *
 * Interface for entity integrations.
 * All entity integrations must implement this interface.
 *
 * @package WPCustomer\Controllers\Integration\Integrations
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration\Integrations;

defined('ABSPATH') || exit;

/**
 * Interface for entity integrations
 *
 * Defines the contract for entity integration classes.
 * Each integration represents a connection between wp-customer
 * and another plugin's entity type (agency, company, etc.).
 *
 * @since 1.0.12
 */
interface EntityIntegrationInterface {

    /**
     * Initialize the integration
     *
     * Called by EntityIntegrationManager during load_integrations().
     * Register your filter hooks here for:
     * - Entity relation configurations
     * - Tab content injection configurations
     * - DataTable access filter configurations
     *
     * @return void
     * @since 1.0.12
     *
     * @example
     * ```php
     * public function init(): void {
     *     add_filter('wp_customer_entity_relation_configs', [$this, 'register_relation_config']);
     *     add_filter('wp_customer_tab_injection_configs', [$this, 'register_tab_config']);
     *     add_filter('wp_customer_datatable_access_configs', [$this, 'register_access_config']);
     * }
     * ```
     */
    public function init(): void;

    /**
     * Get entity type identifier
     *
     * Returns a unique identifier for this entity type.
     * Must match the entity type used in configurations.
     *
     * @return string Entity type (e.g., 'agency', 'company', 'branch')
     * @since 1.0.12
     *
     * @example
     * ```php
     * public function get_entity_type(): string {
     *     return 'agency';
     * }
     * ```
     */
    public function get_entity_type(): string;

    /**
     * Check if integration should load
     *
     * Return false if:
     * - Target plugin is not active
     * - Required dependencies are missing
     * - Current user doesn't have required capabilities
     *
     * Called before init() to determine if integration should be loaded.
     *
     * @return bool True to load integration, false to skip
     * @since 1.0.12
     *
     * @example
     * ```php
     * public function should_load(): bool {
     *     // Check if target plugin is active
     *     return is_plugin_active('wp-agency/wp-agency.php');
     * }
     * ```
     */
    public function should_load(): bool;
}
