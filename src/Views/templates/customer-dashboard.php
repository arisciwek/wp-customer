<?php
/**
 * Customer Dashboard Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer-dashboard.php
 *
 * Description: Main dashboard template untuk manajemen customer.
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Dashboard Section -->
    <div class="wp-customer-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik WP</h2>
                    <div class="wp-customer-stats-container">
                        <div class="wp-customer-stat-box customer-stats">
                            <h3>Total Customer</h3>
                            <p class="wp-customer-stat-number"><span id="total-customers">0</span></p>
                        </div>
                        <div class="wp-customer-stat-box">
                            <h3>Total Cabang</h3>
                            <p class="wp-customer-stat-number" id="total-branches">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-customer-content-area">
        <div id="wp-customer-main-container" class="wp-customer-container">
            <!-- Left Panel -->
            <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-customer-right-panel" class="wp-customer-right-panel hidden">
                <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer-right-panel.php'; ?>
            </div>
        </div>
    </div>

    <!-- Modal Forms -->
    <?php
    require_once WP_CUSTOMER_PATH . 'src/Views/templates/forms/create-customer-form.php';
    require_once WP_CUSTOMER_PATH . 'src/Views/templates/forms/edit-customer-form.php';
    ?>
    <!-- Modal Templates -->
    <?php
    if (function_exists('wp_customer_render_confirmation_modal')) {
        wp_customer_render_confirmation_modal();
    }
    ?>
