<?php

/**
 * Company Dashboard Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company/company/company-dashboard.php
 *
 * Description: Main dashboard template untuk menampilkan perusahaan.
 *              Includes statistics overview, DataTable listing,
 *              right panel details, dan tab management.
 *              Mengatur layout dan component integration.
 *
 * Changelog:
 * 1.0.0 - 2024-02-09
 * - Initial version
 * - Added statistics display
 * - Added company listing
 * - Added panel navigation
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Dashboard Section -->
    <div class="wp-company-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik WP</h2>
                    <div class="wi-stats-container">
                        <div class="wi-stat-box company-stats">
                            <h3>Total Perusahaan</h3>
                            <p class="wi-stat-number"><span id="total-companies">0</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-company-content-area">
        <div id="wp-company-main-container" class="wp-company-container">
            <!-- Left Panel -->
            <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company/company-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-company-right-panel" class="wp-company-right-panel hidden">
                <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company/company-right-panel.php'; ?>
            </div>
        </div>
    </div>
</div>
