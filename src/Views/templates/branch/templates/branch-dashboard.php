<?php
/**
 * Customer Dashboard Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer-branch/src/Views/templates/branch/templates/branch-dashboard.php
 *
 * Description: Main dashboard template untuk manajemen branch.
 *              Includes statistics overview, DataTable listing,
 *              right panel details, dan modal forms.
 *              Mengatur layout dan component integration.
 *
 * Changelog:
 * 1.0.1 - 2024-12-05
 * - Added edit form modal integration
 * - Updated form templates loading
 * - Improved modal management
 *
 * 1.0.0 - 2024-12-03
 * - Initial dashboard implementation
 * - Added statistics display
 * - Added customer listing
 * - Added panel navigation
 */

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Dashboard Section -->
    <div class="wp-customer-branch-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik WP</h2>
                    <div class="wi-stats-container">
                        <div class="wi-stat-box customer-stats">
                            <h3>Total Customer</h3>
                            <p class="wi-stat-number"><span id="total-customers">0</span></p>
                        </div>
                        <div class="wi-stat-box">
                            <h3>Total Cabang</h3>
                            <p class="wi-stat-number" id="total-branches">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-customer-branch-content-area">
        <div id="wp-customer-branch-main-container" class="wp-customer-branch-container">
            <!-- Left Panel -->
            <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/templates/branch-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-customer-branch-right-panel" class="wp-customer-branch-right-panel hidden">
                <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/branch/templates/branch-right-panel.php'; ?>
            </div>
        </div>
    </div>

    <!-- Modal Forms -->
    <?php
    require_once WP_CUSTOMER_PATH . 'src/Views/components/confirmation-modal.php';
    ?>
    <!-- Modal Templates -->
    <?php
    if (function_exists('wp_customer_render_confirmation_modal')) {
        wp_customer_render_confirmation_modal();
    }
    ?>
