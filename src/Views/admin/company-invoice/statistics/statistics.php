<?php
/**
 * Company Invoice Statistics View
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/statistics/statistics.php
 *
 * Description: Statistics cards untuk company invoice dashboard.
 *              Digunakan oleh CompanyInvoiceDashboardController::render_statistics()
 *              Pattern sama dengan company statistics
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation following statistics pattern
 * - Cards: Total, Pending, Paid, Total Amount
 * - AJAX auto-refresh via handle_get_stats()
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-statistics-cards">
    <div class="wpdt-stat-card">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-media-document"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Total Invoices', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-total-invoices">0</div>
        </div>
    </div>

    <div class="wpdt-stat-card wpdt-stat-pending">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-clock"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Pending', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-pending-invoices">0</div>
        </div>
    </div>

    <div class="wpdt-stat-card wpdt-stat-paid">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Paid', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-paid-invoices">0</div>
        </div>
    </div>

    <div class="wpdt-stat-card wpdt-stat-amount">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-money-alt"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Total Paid', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-total-amount">Rp 0</div>
        </div>
    </div>
</div>
