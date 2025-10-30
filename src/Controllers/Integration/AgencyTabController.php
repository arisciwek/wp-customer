<?php
/**
 * Agency Tab Controller
 *
 * Simple controller to inject content into wp-agency tabs.
 * Follows MVC pattern: Controller coordinates Model and View.
 *
 * @package     WPCustomer
 * @subpackage  Controllers/Integration
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Integration/AgencyTabController.php
 *
 * Description: Simple hook handler untuk inject content ke agency tabs.
 *              Tidak hardcode ke "statistics" saja - bisa inject apapun.
 *              MVC compliant: Controller → Model → View
 *
 * Usage:
 * ```php
 * // In wp-customer.php
 * $controller = new AgencyTabController();
 * $controller->init();
 * ```
 *
 * Changelog:
 * 1.1.0 - 2025-10-29 (TODO-2180)
 * - CHANGED: Use wpapp_tab_view_after_content hook instead of wpapp_tab_view_content
 * - REASON: Separate core content rendering from extension injection
 * - BENEFIT: Prevents duplicate rendering when used with TabViewTemplate
 * - RELATED: wp-app-core TODO-1188 (added new hook)
 *
 * 1.0.0 - 2025-10-29
 * - Initial creation (Refactor from over-engineered solution)
 * - Simple hook handler approach
 * - MVC proper: Controller coordinates, Model queries, View displays
 * - Generic: Can inject any content, not just statistics
 */

namespace WPCustomer\Controllers\Integration;

defined('ABSPATH') || exit;

// Load Model
if (!class_exists('WPCustomer\Models\Statistics\CustomerStatisticsModel')) {
    require_once WP_CUSTOMER_PATH . 'src/Models/Statistics/CustomerStatisticsModel.php';
}

use WPCustomer\Models\Statistics\CustomerStatisticsModel;

/**
 * AgencyTabController Class
 *
 * Simple controller to inject customer statistics into agency info tab.
 * Uses generic hook so other plugins can also inject content.
 *
 * @since 1.0.0
 */
class AgencyTabController {

    /**
     * Statistics model instance
     *
     * @var CustomerStatisticsModel
     */
    private $statistics_model;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Lazy load model
        $this->statistics_model = null;
    }

    /**
     * Initialize controller
     *
     * Register hook handler for wp-agency tab content injection.
     *
     * @return void
     * @since 1.0.0
     */
    public function init(): void {
        // Check if wp-agency is active
        if (!$this->is_agency_plugin_active()) {
            return;
        }

        // Register entity relation config for 'agency' entity
        // This allows EntityRelationModel to query customer-agency relations
        add_filter('wp_customer_entity_relation_configs', [$this, 'register_agency_entity_config'], 10, 1);

        // Register hook handler for extension content injection
        // Use wpapp_tab_view_after_content (added in wp-app-core TODO-1188)
        // Priority 20 - extension content after core content
        add_action('wpapp_tab_view_after_content', [$this, 'inject_content'], 20, 3);
    }

    /**
     * Register agency entity configuration
     *
     * Registers 'agency' entity config for EntityRelationModel.
     * Defines how to query customer-agency relations via branches table.
     *
     * @param array $configs Existing entity configs
     * @return array Modified configs with 'agency' added
     *
     * @since 1.0.0
     */
    public function register_agency_entity_config(array $configs): array {
        // Register 'agency' entity config
        // Schema: customer_branches table has agency_id and customer_id
        $configs['agency'] = [
            'bridge_table' => 'app_customer_branches',    // Bridge table (without prefix)
            'entity_column' => 'agency_id',                // Column linking to wp_app_agencies.id
            'customer_column' => 'customer_id',            // Column linking to wp_app_customers.id
            'access_filter' => true,                       // Enable user access filtering
            'cache_group' => 'wp_customer_agency_relations',
            'cache_ttl' => 3600                            // Cache for 1 hour
        ];

        return $configs;
    }

    /**
     * Inject content into agency tab
     *
     * Hook handler for wpapp_tab_view_content.
     * Only injects if:
     * - Entity is 'agency'
     * - Tab is 'info'
     * - Agency data is available
     *
     * @param string $entity Entity type (agency, customer, etc.)
     * @param string $tab_id Tab identifier (info, divisions, etc.)
     * @param array  $data   Data passed from tab (contains agency object)
     * @return void
     *
     * @since 1.0.0
     */
    public function inject_content(string $entity, string $tab_id, array $data): void {
        error_log('=== AgencyTabController::inject_content CALLED ===');
        error_log('Entity: ' . $entity . ', Tab: ' . $tab_id);

        // Only inject for agency entity
        if ($entity !== 'agency') {
            error_log('Skipping: Not agency entity');
            return;
        }

        // Only inject in 'info' tab
        if ($tab_id !== 'info') {
            error_log('Skipping: Not info tab');
            return;
        }

        // Get agency data
        $agency = $data['agency'] ?? null;
        if (!$agency || !isset($agency->id)) {
            error_log('ERROR: No agency data in $data array');
            return;
        }

        error_log('Agency ID: ' . $agency->id . ', Name: ' . $agency->name);

        /**
         * Action: Before customer content injection
         *
         * Allows other plugins to inject content before customer statistics.
         *
         * @param object $agency Agency object
         * @param string $tab_id Tab identifier
         *
         * @since 1.0.0
         */
        do_action('wp_customer_before_agency_tab_content', $agency, $tab_id);

        // Controller coordinates: Get data from Model
        error_log('Calling get_statistics() for agency ID: ' . $agency->id);
        $statistics = $this->get_statistics($agency->id);
        error_log('Statistics retrieved: ' . json_encode($statistics));

        // Controller coordinates: Pass data to View
        error_log('Calling render_view()');
        $this->render_view($statistics, $agency);
        error_log('render_view() completed');

        /**
         * Action: After customer content injection
         *
         * Allows other plugins to inject content after customer statistics.
         *
         * @param object $agency     Agency object
         * @param string $tab_id     Tab identifier
         * @param array  $statistics Statistics data
         *
         * @since 1.0.0
         */
        do_action('wp_customer_after_agency_tab_content', $agency, $tab_id, $statistics);
    }

    /**
     * Get statistics from Model
     *
     * Lazy loads model and retrieves statistics for agency.
     * Respects user access filtering.
     *
     * @param int $agency_id Agency ID
     * @return array Statistics data
     *
     * @since 1.0.0
     */
    private function get_statistics(int $agency_id): array {
        // Lazy load model
        if ($this->statistics_model === null) {
            $this->statistics_model = new CustomerStatisticsModel();
        }

        // Get current user ID for access filtering
        $user_id = get_current_user_id();

        // Get statistics from Model (respects user access)
        return $this->statistics_model->get_agency_customer_statistics($agency_id, $user_id);
    }

    /**
     * Render view template
     *
     * Includes the view template with statistics data.
     * View is responsible for displaying data only.
     *
     * @param array  $statistics Statistics data from Model
     * @param object $agency     Agency object
     * @return void
     *
     * @since 1.0.0
     */
    private function render_view(array $statistics, object $agency): void {
        // Extract data for view
        $customer_count = $statistics['customer_count'] ?? 0;
        $branch_count = $statistics['branch_count'] ?? 0;

        /**
         * Filter: Modify statistics before display
         *
         * Allows plugins to modify statistics data before rendering.
         *
         * @param array  $statistics Statistics data
         * @param int    $agency_id  Agency ID
         * @param int    $user_id    Current user ID
         * @return array Modified statistics
         *
         * @since 1.0.0
         */
        $statistics = apply_filters(
            'wp_customer_agency_statistics_data',
            $statistics,
            $agency->id,
            get_current_user_id()
        );

        // Include view template
        $template_path = WP_CUSTOMER_PATH . 'src/Views/integration/agency-customer-statistics.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            error_log("WP Customer: View template not found at {$template_path}");
        }
    }

    /**
     * Check if wp-agency plugin is active
     *
     * @return bool True if active
     * @since 1.0.0
     */
    private function is_agency_plugin_active(): bool {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('wp-agency/wp-agency.php');
    }
}
