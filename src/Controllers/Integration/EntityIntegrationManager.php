<?php
/**
 * Entity Integration Manager
 *
 * Central registry and orchestrator for entity integrations.
 * Manages registration, loading, and lifecycle of entity integrations.
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration;

use WPCustomer\Controllers\Integration\Integrations\EntityIntegrationInterface;

defined('ABSPATH') || exit;

/**
 * EntityIntegrationManager Class
 *
 * Provides:
 * - Integration registration system
 * - Automatic integration discovery
 * - Integration lifecycle management
 * - Hook-based extensibility
 *
 * @since 1.0.12
 */
class EntityIntegrationManager {

    /**
     * Registered integrations
     *
     * @var array<string, EntityIntegrationInterface>
     */
    private $integrations = [];

    /**
     * Loaded integrations
     *
     * @var array<string, bool>
     */
    private $loaded = [];

    /**
     * Singleton instance
     *
     * @var EntityIntegrationManager|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return EntityIntegrationManager
     * @since 1.0.12
     */
    public static function get_instance(): EntityIntegrationManager {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * Private to enforce singleton pattern.
     *
     * @since 1.0.12
     */
    private function __construct() {
        // Load integrations on init
        add_action('wp_customer_init', [$this, 'load_integrations'], 10);
    }

    /**
     * Register an integration
     *
     * Registers an entity integration. Should be called via filter hook
     * or directly before load_integrations() is executed.
     *
     * @param string                    $entity_type Entity type identifier
     * @param EntityIntegrationInterface $integration Integration instance
     * @return bool True on success, false if already registered
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $manager = EntityIntegrationManager::get_instance();
     * $manager->register_integration('agency', new AgencyIntegration());
     * ```
     */
    public function register_integration(string $entity_type, EntityIntegrationInterface $integration): bool {
        if (isset($this->integrations[$entity_type])) {
            return false; // Already registered
        }

        $this->integrations[$entity_type] = $integration;

        return true;
    }

    /**
     * Load all registered integrations
     *
     * Discovers integrations via filter hook and initializes those that
     * should load (based on dependencies, plugin activation, etc.).
     *
     * Fires action hooks for integration lifecycle events.
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * // Called automatically on 'wp_customer_init' action
     * // Or call manually for testing:
     * $manager->load_integrations();
     * ```
     */
    public function load_integrations(): void {
        /**
         * Action: Before integrations are loaded
         *
         * @param EntityIntegrationManager $manager Manager instance
         * @since 1.0.12
         */
        do_action('wp_customer_before_integrations_load', $this);

        /**
         * Filter: Register entity integrations
         *
         * Register custom entity integrations.
         *
         * @param array $integrations Existing integrations
         * @return array Modified integrations
         *
         * @since 1.0.12
         *
         * @example
         * ```php
         * add_filter('wp_customer_register_integrations', function($integrations) {
         *     $integrations['agency'] = new AgencyIntegration();
         *     $integrations['company'] = new CompanyIntegration();
         *     return $integrations;
         * });
         * ```
         */
        $discovered_integrations = apply_filters('wp_customer_register_integrations', []);

        // Register discovered integrations
        foreach ($discovered_integrations as $entity_type => $integration) {
            if (!$integration instanceof EntityIntegrationInterface) {
                // Log error
                error_log(
                    sprintf(
                        'WP Customer: Invalid integration for entity type "%s". Must implement EntityIntegrationInterface.',
                        $entity_type
                    )
                );
                continue;
            }

            $this->register_integration($entity_type, $integration);
        }

        // Initialize integrations that should load
        foreach ($this->integrations as $entity_type => $integration) {
            /**
             * Filter: Check if integration should load
             *
             * @param bool                        $should_load Whether to load
             * @param string                      $entity_type Entity type
             * @param EntityIntegrationInterface  $integration Integration instance
             * @return bool Modified should_load
             *
             * @since 1.0.12
             */
            $should_load = apply_filters(
                'wp_customer_integration_should_load',
                $integration->should_load(),
                $entity_type,
                $integration
            );

            if (!$should_load) {
                continue;
            }

            /**
             * Action: Before integration init
             *
             * @param string                      $entity_type Entity type
             * @param EntityIntegrationInterface  $integration Integration instance
             * @since 1.0.12
             */
            do_action('wp_customer_before_integration_init', $entity_type, $integration);

            // Initialize integration
            $integration->init();

            // Mark as loaded
            $this->loaded[$entity_type] = true;

            /**
             * Action: After integration init
             *
             * @param string                      $entity_type Entity type
             * @param EntityIntegrationInterface  $integration Integration instance
             * @since 1.0.12
             */
            do_action('wp_customer_after_integration_init', $entity_type, $integration);
        }

        /**
         * Action: After all integrations loaded
         *
         * @param EntityIntegrationManager $manager Manager instance
         * @param array                    $loaded  Loaded entity types
         * @since 1.0.12
         */
        do_action('wp_customer_integrations_loaded', $this, array_keys($this->loaded));
    }

    /**
     * Get specific integration
     *
     * @param string $entity_type Entity type
     * @return EntityIntegrationInterface|null Integration instance or null
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $manager = EntityIntegrationManager::get_instance();
     * $agency_integration = $manager->get_integration('agency');
     * if ($agency_integration) {
     *     // Use integration
     * }
     * ```
     */
    public function get_integration(string $entity_type): ?EntityIntegrationInterface {
        return $this->integrations[$entity_type] ?? null;
    }

    /**
     * Get all registered integrations
     *
     * @return array<string, EntityIntegrationInterface> All integrations
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $manager = EntityIntegrationManager::get_instance();
     * $all = $manager->get_all_integrations();
     * foreach ($all as $entity_type => $integration) {
     *     echo "Registered: {$entity_type}\n";
     * }
     * ```
     */
    public function get_all_integrations(): array {
        return $this->integrations;
    }

    /**
     * Check if integration is loaded
     *
     * @param string $entity_type Entity type
     * @return bool True if loaded, false otherwise
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $manager = EntityIntegrationManager::get_instance();
     * if ($manager->is_integration_loaded('agency')) {
     *     echo "Agency integration is active\n";
     * }
     * ```
     */
    public function is_integration_loaded(string $entity_type): bool {
        return isset($this->loaded[$entity_type]) && $this->loaded[$entity_type];
    }

    /**
     * Get all loaded integrations
     *
     * @return array<string> Array of loaded entity types
     *
     * @since 1.0.12
     */
    public function get_loaded_integrations(): array {
        return array_keys(array_filter($this->loaded));
    }

    /**
     * Unregister an integration
     *
     * Removes a registered integration. Use with caution.
     *
     * @param string $entity_type Entity type
     * @return bool True if unregistered, false if not found
     *
     * @since 1.0.12
     */
    public function unregister_integration(string $entity_type): bool {
        if (!isset($this->integrations[$entity_type])) {
            return false;
        }

        unset($this->integrations[$entity_type]);
        unset($this->loaded[$entity_type]);

        return true;
    }

    /**
     * Get integration count
     *
     * @param bool $loaded_only Count only loaded integrations
     * @return int Integration count
     *
     * @since 1.0.12
     */
    public function get_integration_count(bool $loaded_only = false): int {
        if ($loaded_only) {
            return count($this->loaded);
        }

        return count($this->integrations);
    }
}
