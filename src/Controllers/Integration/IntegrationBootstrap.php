<?php
/**
 * Integration Bootstrap
 *
 * Initializes and bootstraps the Generic Entity Integration Framework.
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration;

use WPCustomer\Models\Relation\EntityRelationModel;
use WPCustomer\Controllers\Integration\Integrations\AgencyIntegration;

defined('ABSPATH') || exit;

/**
 * IntegrationBootstrap Class
 *
 * Bootstraps the integration framework:
 * - Initializes core components
 * - Registers built-in integrations
 * - Loads external integrations
 *
 * @since 1.0.12
 */
class IntegrationBootstrap {

    /**
     * Entity relation model
     *
     * @var EntityRelationModel
     */
    private $model;

    /**
     * Integration manager
     *
     * @var EntityIntegrationManager
     */
    private $manager;

    /**
     * Tab content injector
     *
     * @var TabContentInjector
     */
    private $injector;

    /**
     * DataTable access filter
     *
     * @var DataTableAccessFilter
     */
    private $access_filter;

    /**
     * Bootstrap flag
     *
     * @var bool
     */
    private static $bootstrapped = false;

    /**
     * Constructor
     *
     * @since 1.0.12
     */
    public function __construct() {
        // Prevent multiple bootstrap
        if (self::$bootstrapped) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.12
     */
    private function init_hooks(): void {
        // Bootstrap on wp_customer_init action
        add_action('wp_customer_init', [$this, 'bootstrap'], 5);
    }

    /**
     * Bootstrap the integration framework
     *
     * Initializes all core components and loads integrations.
     *
     * @return void
     * @since 1.0.12
     */
    public function bootstrap(): void {
        // Prevent multiple bootstrap
        if (self::$bootstrapped) {
            return;
        }

        /**
         * Action: Before integration framework bootstrap
         *
         * @since 1.0.12
         */
        do_action('wp_customer_before_integration_bootstrap');

        // 1. Initialize EntityRelationModel
        $this->model = new EntityRelationModel();

        // 2. Register built-in integrations
        // IMPORTANT: Must be done BEFORE DataTableAccessFilter initialization
        // so configs are available when filter hooks are registered
        $this->register_builtin_integrations();

        // 3. Initialize TabContentInjector
        $this->injector = new TabContentInjector($this->model);

        // 4. Initialize DataTableAccessFilter
        // Reads configs and registers filter hooks
        $this->access_filter = new DataTableAccessFilter($this->model);

        // 5. Initialize AgencyAccessCapabilityFilter
        // Grants customer roles access to wp-agency menu (with filtered data)
        new AgencyAccessCapabilityFilter();

        // 6. Get EntityIntegrationManager instance
        $this->manager = EntityIntegrationManager::get_instance();

        // Note: EntityIntegrationManager will automatically call load_integrations()
        // on 'wp_customer_init' action (priority 10), which runs after this (priority 5)

        // Mark as bootstrapped
        self::$bootstrapped = true;

        /**
         * Action: After integration framework bootstrap
         *
         * @param IntegrationBootstrap $bootstrap Bootstrap instance
         *
         * @since 1.0.12
         */
        do_action('wp_customer_after_integration_bootstrap', $this);
    }

    /**
     * Register built-in integrations
     *
     * Registers integrations that ship with wp-customer.
     *
     * @return void
     * @since 1.0.12
     */
    private function register_builtin_integrations(): void {
        /**
         * Filter: wp_customer_register_integrations
         *
         * Register entity integrations.
         * This is where AgencyIntegration and future integrations are registered.
         */
        add_filter('wp_customer_register_integrations', function($integrations) {
            // Register AgencyIntegration
            $integrations['agency'] = new AgencyIntegration();

            /**
             * Filter: Modify built-in integrations
             *
             * @param array $builtin_integrations Built-in integrations
             * @return array Modified integrations
             *
             * @since 1.0.12
             */
            $integrations = apply_filters('wp_customer_builtin_integrations', $integrations);

            return $integrations;
        }, 5); // Priority 5 - before EntityIntegrationManager loads (priority 10)
    }

    /**
     * Get entity relation model
     *
     * @return EntityRelationModel|null Model instance or null if not bootstrapped
     * @since 1.0.12
     */
    public function get_model(): ?EntityRelationModel {
        return $this->model;
    }

    /**
     * Get integration manager
     *
     * @return EntityIntegrationManager|null Manager instance or null if not bootstrapped
     * @since 1.0.12
     */
    public function get_manager(): ?EntityIntegrationManager {
        return $this->manager;
    }

    /**
     * Get tab content injector
     *
     * @return TabContentInjector|null Injector instance or null if not bootstrapped
     * @since 1.0.12
     */
    public function get_injector(): ?TabContentInjector {
        return $this->injector;
    }

    /**
     * Get DataTable access filter
     *
     * @return DataTableAccessFilter|null Filter instance or null if not bootstrapped
     * @since 1.0.12
     */
    public function get_access_filter(): ?DataTableAccessFilter {
        return $this->access_filter;
    }

    /**
     * Check if framework is bootstrapped
     *
     * @return bool True if bootstrapped
     * @since 1.0.12
     */
    public static function is_bootstrapped(): bool {
        return self::$bootstrapped;
    }
}
