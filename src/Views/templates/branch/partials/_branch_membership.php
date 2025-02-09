<?php
/**
 * Customer Membership Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Template for displaying customer membership information
 *              Shows current membership status, staff usage, capabilities,
 *              and upgrade options
 */

defined('ABSPATH') || exit;
?>
<div id="membership-info" class="tab-content">
    <!-- Current membership status section - sudah ada -->
    <div class="postbox membership-status-card">
        <h3 class="hndle">
            <span class="dashicons dashicons-buddicons-groups"></span>
            <?php _e('Status Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <!-- Status Badge -->
            <div class="membership-status-header">
                <span id="membership-level-name"></span>
                <span id="membership-status" class="status-badge"></span>
            </div>

            <!-- Staff Usage Section -->
            <div class="staff-usage-section">
                <h4><?php _e('Penggunaan Staff', 'wp-customer'); ?></h4>
                <div class="staff-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="staff-usage-bar"></div>
                    </div>
                    <div class="usage-text">
                        <span id="staff-usage-count">0</span> / 
                        <span id="staff-usage-limit">0</span> 
                        <?php _e('staff', 'wp-customer'); ?>
                    </div>
                </div>
            </div>

            <!-- Active Capabilities -->
            <div class="capabilities-section">
                <h4><?php _e('Fitur Aktif', 'wp-customer'); ?></h4>
                <ul id="active-capabilities" class="capability-list"></ul>
            </div>

            <!-- Period Information -->
            <div class="period-section">
                <h4><?php _e('Periode Membership', 'wp-customer'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Mulai', 'wp-customer'); ?></th>
                        <td><span id="membership-start-date">-</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Berakhir', 'wp-customer'); ?></th>
                        <td><span id="membership-end-date">-</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Level Membership section -->
    <div class="postbox">
        <h3 class="hndle">
            <span class="dashicons dashicons-star-filled"></span>
            <?php _e('Level Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <div class="upgrade-cards-container">
                <!-- Regular Card -->
                <div class="upgrade-card">
                    <h4 id="regular-name">Regular</h4>
                    <div class="card-content">
                        <!-- Harga -->
                        <div class="upgrade-price">
                            <span class="price-amount" id="regular-price"></span>
                            <span class="price-period">/ bulan</span>
                        </div>

                        <!-- Staff Limit -->
                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span id="regular-staff-limit"></span>
                        </div>

                        <!-- Features List -->
                        <div class="features-container">
                            <ul class="plan-features" id="regular-features"></ul>
                        </div>

                        <!-- Trial Badge -->
                        <div class="trial-badge" id="regular-trial"></div>

                        <!-- Tombol Upgrade -->
                        <div id="tombol-upgrade-regular"></div>
                    </div>
                </div>

                <!-- Prioritas Card (sama tapi ID berbeda) -->
                <div class="upgrade-card">
                    <h4 id="prioritas-name">Prioritas</h4>
                    <div class="card-content">
                        <div class="upgrade-price">
                            <span class="price-amount" id="prioritas-price"></span>
                            <span class="price-period">/ bulan</span>
                        </div>

                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span id="prioritas-staff-limit"></span>
                        </div>

                        <div class="features-container">
                            <ul class="plan-features" id="prioritas-features"></ul>
                        </div>

                        <div class="trial-badge" id="prioritas-trial"></div>
                        <div id="tombol-upgrade-prioritas"></div>
                    </div>
                </div>

                <!-- Utama Card -->
                <div class="upgrade-card">
                    <h4 id="utama-name">Utama</h4>
                    <div class="card-content">
                        <div class="upgrade-price">
                            <span class="price-amount" id="utama-price"></span>
                            <span class="price-period">/ bulan</span>
                        </div>

                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span id="utama-staff-limit"></span>
                        </div>

                        <div class="features-container">
                            <ul class="plan-features" id="utama-features"></ul>
                        </div>

                        <div class="trial-badge" id="utama-trial"></div>
                        <div id="tombol-upgrade-utama"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
