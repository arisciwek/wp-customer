<?php
/**
 * Agency Integration Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Integration
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Integration/AgencyIntegrationController.php
 *
 * Description: Integrates customer data into wp-agency dashboard.
 *              Hooks into wpapp_tab_view_content to inject customer statistics
 *              into agency detail tabs.
 *              Implements Hook-Based Content Injection Pattern.
 *
 * Hook Pattern:
 * - Action: wpapp_tab_view_content (priority 20)
 * - Triggered by: wp-agency AgencyDashboardController
 * - Injects: Customer count statistics for agency
 *
 * Features:
 * - Single SQL query for customer count
 * - User access filtering (platform staff + customer employees)
 * - Clean separation from wp-agency code
 * - No modification of wp-agency files needed
 *
 * Changelog:
 * 1.0.0 - 2025-10-28
 * - Initial implementation (Task-2177 / Task-3085)
 * - Hook-based customer statistics injection
 * - User access filtering
 * - Single SQL query for performance
 */

namespace WPCustomer\Controllers\Integration;

defined('ABSPATH') || exit;

class AgencyIntegrationController {

    /**
     * Constructor
     * Register hook for agency tab content injection
     */
    public function __construct() {
        // Hook into agency tab content at priority 20 (after wp-agency core at priority 10)
        add_action('wpapp_tab_view_content', [$this, 'inject_customer_statistics'], 20, 3);
    }

    /**
     * Inject customer statistics into agency info tab
     *
     * Hooked to: wpapp_tab_view_content (priority 20)
     *
     * This method injects customer count statistics into the agency detail tab.
     * Uses single SQL query with JOIN for optimal performance.
     * Filters by user access (platform staff or customer employee).
     *
     * @param string $entity   Entity type (e.g., 'agency')
     * @param string $tab_id   Tab identifier (e.g., 'info')
     * @param array  $data     Data array containing agency object
     * @return void
     */
    public function inject_customer_statistics($entity, $tab_id, $data): void {
        // Only inject into agency info tab
        if ($entity !== 'agency' || $tab_id !== 'info') {
            return;
        }

        // Get agency object from data
        $agency = $data['agency'] ?? null;
        if (!$agency || !isset($agency->id)) {
            return;
        }

        // Get customer count with user access filter
        $customer_count = $this->get_customer_count($agency->id);

        // Render customer statistics section
        $this->render_customer_statistics($customer_count);
    }

    /**
     * Get customer count for agency with user access filter
     *
     * Uses single SQL query with JOINs for optimal performance:
     * 1. Customers → Branches (via customer_id)
     * 2. Filter by agency_id
     * 3. Filter by user access (platform staff OR customer employee)
     *
     * @param int $agency_id Agency ID
     * @return int Customer count
     */
    private function get_customer_count($agency_id): int {
        global $wpdb;

        $current_user_id = get_current_user_id();

        // Single SQL query with user access filter
        $sql = $wpdb->prepare("
            SELECT COUNT(DISTINCT c.id) as customer_count
            FROM {$wpdb->prefix}app_customers c
            INNER JOIN {$wpdb->prefix}app_customer_branches b ON c.id = b.customer_id
            WHERE b.agency_id = %d
            AND (
                -- Platform staff can see all customers
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->prefix}app_platform_staff ps
                    WHERE ps.user_id = %d
                )
                OR
                -- Customer employee can only see their customers
                EXISTS (
                    SELECT 1
                    FROM {$wpdb->prefix}app_customer_employees ce
                    WHERE ce.customer_id = c.id
                    AND ce.user_id = %d
                )
            )
        ", $agency_id, $current_user_id, $current_user_id);

        $count = $wpdb->get_var($sql);

        // Allow plugins to filter the count
        return apply_filters('wp_customer_agency_customer_count', intval($count), $agency_id, $current_user_id);
    }

    /**
     * Render customer statistics HTML
     *
     * Displays customer count in a section matching agency detail style.
     * Uses agency-detail-* classes for consistent styling.
     *
     * @param int $customer_count Customer count
     * @return void
     */
    private function render_customer_statistics($customer_count): void {
        ?>
        <!-- Customer Statistics Section (Injected by wp-customer plugin) -->
        <div class="agency-detail-section wp-customer-integration">
            <h3><?php esc_html_e('Statistik Customer', 'wp-customer'); ?></h3>

            <div class="agency-detail-row">
                <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
                <span class="customer-count-value">
                    <strong><?php echo esc_html($customer_count); ?></strong>
                </span>
            </div>

            <?php if ($customer_count > 0): ?>
            <div class="agency-detail-row">
                <label><?php esc_html_e('Keterangan', 'wp-customer'); ?>:</label>
                <span class="customer-count-note">
                    <?php esc_html_e('Customer yang terhubung dengan agency ini', 'wp-customer'); ?>
                </span>
            </div>
            <?php endif; ?>

            <?php
            // Allow other plugins to add content
            do_action('wp_customer_after_agency_statistics', $customer_count);
            ?>
        </div>
        <?php
    }
}
