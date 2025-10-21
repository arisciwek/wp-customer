<?php
/**
 * Company Invoice Dashboard Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company-invoice/company-invoice-dashboard.php
 *
 * Description: Main dashboard template untuk manajemen company invoice.
 *              Includes statistics overview, DataTable listing,
 *              right panel details, dan modal forms.
 *              Mengatur layout dan component integration.
 *
 * Changelog:
 * 1.0.1 - 2025-01-17 (Review-07)
 * - Added membership invoice payment modal template
 * - Improved template organization
 *
 * 1.0.0 - 2024-12-25
 * - Initial dashboard implementation
 * - Added statistics display
 * - Added invoice listing
 * - Added panel navigation
 */

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Dashboard Section -->
    <div class="wp-company-invoice-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik Invoice Perusahaan</h2>
                    <div class="wi-stats-container">
                        <div class="wi-stat-box invoice-stats">
                            <h3>Total Invoice</h3>
                            <p class="wi-stat-number"><span id="total-invoices">0</span></p>
                        </div>
                        <div class="wi-stat-box">
                            <h3>Invoice Belum Dibayar</h3>
                            <p class="wi-stat-number" id="pending-invoices">0</p>
                        </div>
                        <div class="wi-stat-box">
                            <h3>Total Pembayaran</h3>
                            <p class="wi-stat-number" id="total-paid-amount">Rp 0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-company-invoice-content-area">
        <div id="wp-company-invoice-main-container" class="wp-company-invoice-container">
            <!-- Left Panel -->
            <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/company-invoice-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-company-invoice-right-panel" class="wp-company-invoice-right-panel hidden">
                <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/company-invoice-right-panel.php'; ?>
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

    <!-- Payment Modal Template -->
    <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php'; ?>

    <!-- Payment Proof Modal Template -->
    <?php require_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php'; ?>
