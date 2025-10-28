<?php
/**
 * Tab Content Injector
 *
 * Generic controller for injecting content into entity tabs.
 * Supports configuration-based injection with template hierarchy.
 *
 * @package WPCustomer\Controllers\Integration
 * @since 1.0.12
 */

namespace WPCustomer\Controllers\Integration;

use WPCustomer\Models\Relation\EntityRelationModel;

defined('ABSPATH') || exit;

/**
 * TabContentInjector Class
 *
 * Provides:
 * - Configuration-based tab content injection
 * - Template hierarchy (entity-specific → generic → theme)
 * - Data fetching via EntityRelationModel
 * - Filter hooks for customization
 *
 * @since 1.0.12
 */
class TabContentInjector {

    /**
     * Entity relation model
     *
     * @var EntityRelationModel
     */
    private $model;

    /**
     * Tab injection configurations
     *
     * @var array
     */
    private $configs = [];

    /**
     * Constructor
     *
     * @param EntityRelationModel|null $model Entity relation model instance (optional)
     * @since 1.0.12
     */
    public function __construct(?EntityRelationModel $model = null) {
        $this->model = $model ?: new EntityRelationModel();
        $this->load_configs();
        $this->register_hooks();
    }

    /**
     * Load tab injection configurations
     *
     * Configurations registered via filter hook by integration classes.
     *
     * @since 1.0.12
     */
    private function load_configs(): void {
        /**
         * Filter: wp_customer_tab_injection_configs
         *
         * Register tab injection configurations.
         *
         * @param array $configs Tab injection configurations
         * @return array Modified configurations
         *
         * @since 1.0.12
         *
         * @example
         * ```php
         * add_filter('wp_customer_tab_injection_configs', function($configs) {
         *     $configs['agency'] = [
         *         'tabs' => ['info', 'details'],
         *         'template' => 'statistics-simple',
         *         'label' => 'Customer Statistics',
         *         'position' => 'after_metadata',
         *         'priority' => 20
         *     ];
         *     return $configs;
         * });
         * ```
         */
        $this->configs = apply_filters('wp_customer_tab_injection_configs', []);
    }

    /**
     * Register WordPress hooks
     *
     * @since 1.0.12
     */
    private function register_hooks(): void {
        // Register tab content injection hooks for each configured entity
        foreach ($this->configs as $entity_type => $config) {
            $priority = $config['priority'] ?? 20;
            add_action('wpapp_tab_view_content', [$this, 'inject_content'], $priority, 3);
        }
    }

    /**
     * Inject content into entity tab
     *
     * Called by wpapp_tab_view_content action hook.
     * Checks configuration and renders appropriate template.
     *
     * @param string $entity  Entity type
     * @param string $tab_id  Tab identifier
     * @param array  $data    Tab data
     * @return void
     *
     * @since 1.0.12
     */
    public function inject_content(string $entity, string $tab_id, array $data): void {
        // Check if entity has injection config
        if (!isset($this->configs[$entity])) {
            return;
        }

        $config = $this->configs[$entity];

        // Check if should inject in this tab
        $tabs = $config['tabs'] ?? [];
        if (!in_array($tab_id, $tabs)) {
            return;
        }

        // Get entity data
        $entity_data = $data[$entity] ?? null;
        if (!$entity_data || !isset($entity_data->id)) {
            return;
        }

        /**
         * Filter: Check if content should be injected
         *
         * @param bool   $should_inject Whether to inject
         * @param string $entity        Entity type
         * @param string $tab_id        Tab ID
         * @param array  $data          Tab data
         * @param array  $config        Injection config
         * @return bool Modified should_inject
         *
         * @since 1.0.12
         */
        $should_inject = apply_filters(
            'wp_customer_should_inject_tab_content',
            true,
            $entity,
            $tab_id,
            $data,
            $config
        );

        if (!$should_inject) {
            return;
        }

        // Fetch statistics data
        $user_id = get_current_user_id();

        try {
            $statistics = [
                'customer_count' => $this->model->get_customer_count_for_entity($entity, $entity_data->id, $user_id),
                'branch_count' => $this->model->get_branch_count_for_entity($entity, $entity_data->id, $user_id)
            ];
        } catch (\Exception $e) {
            // Log error and return
            error_log("WP Customer: Error fetching statistics for {$entity} #{$entity_data->id}: " . $e->getMessage());
            return;
        }

        /**
         * Filter: Modify statistics data before rendering
         *
         * @param array  $statistics Statistics data
         * @param string $entity     Entity type
         * @param int    $entity_id  Entity ID
         * @param string $tab_id     Tab ID
         * @return array Modified statistics
         *
         * @since 1.0.12
         */
        $statistics = apply_filters(
            'wp_customer_tab_injection_statistics',
            $statistics,
            $entity,
            $entity_data->id,
            $tab_id
        );

        // Prepare template variables
        $template_vars = [
            'entity_type' => $entity,
            'entity_data' => $entity_data,
            'statistics' => $statistics,
            'label' => $config['label'] ?? __('Customer Statistics', 'wp-customer'),
            'config' => $config
        ];

        /**
         * Filter: Modify template variables
         *
         * @param array  $template_vars Template variables
         * @param string $entity        Entity type
         * @param string $tab_id        Tab ID
         * @param array  $config        Injection config
         * @return array Modified variables
         *
         * @since 1.0.12
         */
        $template_vars = apply_filters(
            'wp_customer_tab_injection_template_vars',
            $template_vars,
            $entity,
            $tab_id,
            $config
        );

        // Get template name
        $template = $config['template'] ?? 'statistics-simple';

        // Load and render template
        $this->load_template($entity, $template, $template_vars);
    }

    /**
     * Load template file
     *
     * Searches for template in hierarchy:
     * 1. Entity-specific template
     * 2. Generic template
     * 3. Theme override (if enabled)
     *
     * @param string $entity_type Entity type
     * @param string $template    Template name
     * @param array  $vars        Template variables
     * @return void
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $injector = new TabContentInjector();
     * $injector->load_template('agency', 'statistics-simple', [
     *     'customer_count' => 5,
     *     'branch_count' => 3
     * ]);
     * ```
     */
    public function load_template(string $entity_type, string $template, array $vars = []): void {
        // Get template path
        $template_path = $this->get_template_path($entity_type, $template);

        if (!$template_path) {
            // Template not found - render fallback
            $this->render_fallback($entity_type, $vars);
            return;
        }

        /**
         * Action: Before template is loaded
         *
         * @param string $template_path Template file path
         * @param string $entity_type   Entity type
         * @param string $template      Template name
         * @param array  $vars          Template variables
         *
         * @since 1.0.12
         */
        do_action('wp_customer_before_load_template', $template_path, $entity_type, $template, $vars);

        // Extract variables for template
        extract($vars);

        // Load template
        include $template_path;

        /**
         * Action: After template is loaded
         *
         * @param string $template_path Template file path
         * @param string $entity_type   Entity type
         * @param string $template      Template name
         *
         * @since 1.0.12
         */
        do_action('wp_customer_after_load_template', $template_path, $entity_type, $template);
    }

    /**
     * Get template file path
     *
     * Implements template hierarchy:
     * 1. Entity-specific: entity-specific/{entity}-{template}.php
     * 2. Generic: templates/{template}.php
     * 3. Theme override: {theme}/wp-customer/integration/{entity}-{template}.php
     *
     * @param string $entity_type Entity type
     * @param string $template    Template name
     * @return string|false Template path or false if not found
     *
     * @since 1.0.12
     *
     * @example
     * ```php
     * $injector = new TabContentInjector();
     * $path = $injector->get_template_path('agency', 'statistics-simple');
     * // Returns: /path/to/wp-customer/src/Views/integration/templates/statistics-simple.php
     * ```
     */
    public function get_template_path(string $entity_type, string $template) {
        $base_path = WP_CUSTOMER_PATH . 'src/Views/integration/';

        // Priority 1: Entity-specific template
        $entity_specific = $base_path . "entity-specific/{$entity_type}-{$template}.php";
        if (file_exists($entity_specific)) {
            return $entity_specific;
        }

        // Priority 2: Generic template
        $generic = $base_path . "templates/{$template}.php";
        if (file_exists($generic)) {
            return $generic;
        }

        // Priority 3: Theme override (if enabled)
        /**
         * Filter: Enable theme template overrides
         *
         * @param bool   $enable      Whether to enable theme overrides
         * @param string $entity_type Entity type
         * @param string $template    Template name
         * @return bool Modified enable
         *
         * @since 1.0.12
         */
        $enable_theme_override = apply_filters(
            'wp_customer_enable_theme_template_override',
            false,
            $entity_type,
            $template
        );

        if ($enable_theme_override) {
            $theme_template = get_stylesheet_directory() . "/wp-customer/integration/{$entity_type}-{$template}.php";
            if (file_exists($theme_template)) {
                return $theme_template;
            }
        }

        /**
         * Filter: Modify template path
         *
         * Last chance to provide custom template path.
         *
         * @param string|false $path         Template path or false
         * @param string       $entity_type  Entity type
         * @param string       $template     Template name
         * @return string|false Modified path
         *
         * @since 1.0.12
         */
        return apply_filters('wp_customer_template_path', false, $entity_type, $template);
    }

    /**
     * Render fallback output
     *
     * Used when template file not found.
     * Renders basic HTML output.
     *
     * @param string $entity_type Entity type
     * @param array  $vars        Template variables
     * @return void
     *
     * @since 1.0.12
     */
    private function render_fallback(string $entity_type, array $vars): void {
        $label = $vars['label'] ?? __('Customer Statistics', 'wp-customer');
        $statistics = $vars['statistics'] ?? [];

        ?>
        <div class="wpapp-detail-section wp-customer-integration">
            <h3><?php echo esc_html($label); ?></h3>
            <div class="wpapp-detail-row">
                <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
                <span><strong><?php echo esc_html($statistics['customer_count'] ?? 0); ?></strong></span>
            </div>
            <div class="wpapp-detail-row">
                <label><?php esc_html_e('Total Branch', 'wp-customer'); ?>:</label>
                <span><strong><?php echo esc_html($statistics['branch_count'] ?? 0); ?></strong></span>
            </div>
        </div>
        <?php
    }

    /**
     * Get loaded configurations
     *
     * @return array All configurations
     * @since 1.0.12
     */
    public function get_configs(): array {
        return $this->configs;
    }

    /**
     * Check if entity has injection configured
     *
     * @param string $entity_type Entity type
     * @return bool True if configured
     * @since 1.0.12
     */
    public function has_injection_config(string $entity_type): bool {
        return isset($this->configs[$entity_type]);
    }
}
