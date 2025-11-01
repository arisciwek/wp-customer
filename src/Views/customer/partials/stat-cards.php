<?php
/**
 * Customer Dashboard - Statistics Cards
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/partials/stat-cards.php
 *
 * Description: Statistics cards untuk customer dashboard.
 *              Shows total, active, and inactive counts.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following platform-staff pattern
 * - Statistics cards with local scope classes (customer-*)
 */

defined('ABSPATH') || exit;

// Data passed from controller
$total = $total ?? 0;
$active = $active ?? 0;
$inactive = $inactive ?? 0;
?>

<div class="customer-statistics-cards">
    <!-- Total Customers Card -->
    <div class="customer-stat-card customer-theme-blue">
        <div class="customer-stat-icon">
            <span class="dashicons dashicons-businessperson"></span>
        </div>
        <div class="customer-stat-content">
            <div class="customer-stat-label"><?php echo esc_html__('Total Customers', 'wp-customer'); ?></div>
            <div class="customer-stat-value" id="stat-total-customers"><?php echo esc_html($total); ?></div>
        </div>
    </div>

    <!-- Active Customers Card -->
    <div class="customer-stat-card customer-theme-green">
        <div class="customer-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="customer-stat-content">
            <div class="customer-stat-label"><?php echo esc_html__('Active', 'wp-customer'); ?></div>
            <div class="customer-stat-value" id="stat-active-customers"><?php echo esc_html($active); ?></div>
        </div>
    </div>

    <!-- Inactive Customers Card -->
    <div class="customer-stat-card customer-theme-orange">
        <div class="customer-stat-icon">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="customer-stat-content">
            <div class="customer-stat-label"><?php echo esc_html__('Inactive', 'wp-customer'); ?></div>
            <div class="customer-stat-value" id="stat-inactive-customers"><?php echo esc_html($inactive); ?></div>
        </div>
    </div>
</div>
