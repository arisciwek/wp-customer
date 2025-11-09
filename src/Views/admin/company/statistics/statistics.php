<?php
/**
 * Company Statistics View
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/statistics/statistics.php
 *
 * Description: Statistics cards untuk company dashboard.
 *              Digunakan oleh CompanyDashboardController::render_statistics()
 *              Pattern sama dengan customer statistics
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation following customer statistics pattern
 * - Cards: Total, Active, Inactive
 * - AJAX auto-refresh via handle_get_stats()
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-statistics-cards">
    <div class="wpdt-stat-card">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-building"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Total Companies', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-total-companies">0</div>
        </div>
    </div>

    <div class="wpdt-stat-card wpdt-stat-active">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Active', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-active-companies">0</div>
        </div>
    </div>

    <div class="wpdt-stat-card wpdt-stat-inactive">
        <div class="wpdt-stat-icon">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="wpdt-stat-content">
            <div class="wpdt-stat-label"><?php esc_html_e('Inactive', 'wp-customer'); ?></div>
            <div class="wpdt-stat-value" id="stat-inactive-companies">0</div>
        </div>
    </div>
</div>
