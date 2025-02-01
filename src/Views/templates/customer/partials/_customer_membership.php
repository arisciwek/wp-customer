<?php
/**
 * Customer Membership Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer/partials/_customer_membership.php
 *
 * Description: Template untuk menampilkan informasi membership customer
 *              Menampilkan status membership aktif, penggunaan staff,
 *              fitur yang tersedia, dan opsi upgrade ke level yang
 *              lebih tinggi. Template ini bersifat read-only dengan
 *              opsi aksi upgrade membership.
 *
 * Components:
 * - Membership status card
 * - Staff usage progress bar
 * - Active capabilities list
 * - Upgrade plan cards (Regular/Priority/Utama)
 * 
 * Dependencies:
 * - wp-customer-membership.css
 * - wp-customer-membership.js
 * - WP_Customer_Settings class
 * - membership-settings.php
 *
 * Changelog:
 * v1.0.0 - 2024-01-10
 * - Initial version
 * - Added membership status display
 * - Added staff usage visualization
 * - Added capabilities list
 * - Added upgrade plan options
 * - Integrated with membership settings
 */

defined('ABSPATH') || exit;
?>
<div id="membership-info" class="tab-content">
    <!-- Current Membership Status in a card -->
    <div class="membership-status-card">
        <h3><?php _e('Status Membership Saat Ini', 'wp-customer'); ?></h3>
        <div class="membership-content">
            <!-- Staff Usage -->
            <div class="staff-usage-section">
                <h4><?php _e('Penggunaan Staff', 'wp-customer'); ?></h4>
                <div class="staff-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="staff-usage-bar"></div>
                    </div>
                    <div class="usage-text">
                        <span id="staff-usage-count"></span> / <span id="staff-usage-limit"></span> staff
                    </div>
                </div>
            </div>

            <!-- Capabilities -->
            <div class="capabilities-section">
                <h4><?php _e('Fitur Aktif', 'wp-customer'); ?></h4>
                <ul class="capability-list" id="active-capabilities"></ul>
            </div>
        </div>
    </div>

    <!-- Upgrade Section Title -->
    <h3 class="upgrade-section-title"><?php _e('Upgrade Membership', 'wp-customer'); ?></h3>

    <!-- Upgrade Cards Container -->
    <div class="upgrade-cards-container">
        <!-- Regular Plan Card -->
        <div class="upgrade-card">
            <h4><?php _e('Regular', 'wp-customer'); ?></h4>
            <ul class="plan-features">
                <li><?php _e('Maksimal 2 staff', 'wp-customer'); ?></li>
                <li><?php _e('Dapat menambah staff', 'wp-customer'); ?></li>
                <li><?php _e('1 departemen', 'wp-customer'); ?></li>
            </ul>
            <button type="button" class="button upgrade-button" data-plan="regular">
                <?php _e('Upgrade ke Regular', 'wp-customer'); ?>
            </button>
        </div>

        <!-- Priority Plan Card -->
        <div class="upgrade-card">
            <h4><?php _e('Priority', 'wp-customer'); ?></h4>
            <ul class="plan-features">
                <li><?php _e('Maksimal 5 staff', 'wp-customer'); ?></li>
                <li><?php _e('Dapat menambah staff', 'wp-customer'); ?></li>
                <li><?php _e('Dapat export data', 'wp-customer'); ?></li>
                <li><?php _e('3 departemen', 'wp-customer'); ?></li>
            </ul>
            <button type="button" class="button upgrade-button" data-plan="priority">
                <?php _e('Upgrade ke Priority', 'wp-customer'); ?>
            </button>
        </div>

        <!-- Utama Plan Card -->
        <div class="upgrade-card">
            <h4><?php _e('Utama', 'wp-customer'); ?></h4>
            <ul class="plan-features">
                <li><?php _e('Unlimited staff', 'wp-customer'); ?></li>
                <li><?php _e('Semua fitur Priority', 'wp-customer'); ?></li>
                <li><?php _e('Dapat bulk import', 'wp-customer'); ?></li>
                <li><?php _e('Unlimited departemen', 'wp-customer'); ?></li>
            </ul>
            <button type="button" class="button upgrade-button" data-plan="utama">
                <?php _e('Upgrade ke Utama', 'wp-customer'); ?>
            </button>
        </div>
    </div>
</div>
