<?php
/**
 * Customer Membership Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Customer/Partials
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path : /wp-customer/src/Views/templates/customer/partials/_customer_membership.php
 * 
 * Description: Template untuk menampilkan informasi membership customer
 *              Termasuk status membership, penggunaan resource, dan opsi upgrade
 * 
 * Changelog:
 * v2.0.0 - 2025-03-12
 * - Redesign tampilan membership
 * - Tambah fitur upgrade level
 * - Tambah progress bar usage
 * - Tampilkan perbandingan fitur antar level
 */

defined('ABSPATH') || exit;

// Get current customer ID
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<div id="membership-info" class="tab-content">
    <input type="hidden" id="current-customer-id" value="<?php echo esc_attr($customer_id); ?>">
    
    <!-- Current membership status section -->
    <div class="postbox membership-status-card">
        <h3 class="hndle">
            <span class="dashicons dashicons-buddicons-groups"></span>
            <?php _e('Status Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <!-- Status Badge -->
            <div class="membership-status-header">
                <div class="level-info">
                    <span id="membership-level-name" class="level-badge">-</span>
                    <span id="membership-status" class="status-badge">-</span>
                </div>
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
                <ul id="active-capabilities" class="capability-list">
                    <li class="loading-placeholder"><?php _e('Memuat data...', 'wp-customer'); ?></li>
                </ul>
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
    <div class="postbox membership-levels-card">
        <h3 class="hndle">
            <span class="dashicons dashicons-star-filled"></span>
            <?php _e('Level Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <!-- Period selection for upgrades -->
            <div class="period-selector-container">
                <label for="period-selector"><?php _e('Pilih Periode:', 'wp-customer'); ?></label>
                <select id="period-selector" class="period-selector">
                    <option value="1">1 bulan</option>
                    <option value="3">3 bulan</option>
                    <option value="6">6 bulan</option>
                    <option value="12">12 bulan</option>
                </select>
            </div>
            
            <div class="upgrade-cards-container">
                <!-- Regular Card -->
                <div class="upgrade-card" id="regular-card" data-level="regular">
                    <div class="card-header">
                        <h4 id="regular-name">Regular</h4>
                        <div class="price">
                            <span class="price-amount" id="regular-price">-</span>
                            <span class="price-period">/ bulan</span>
                        </div>
                        <div class="period-details"></div>
                    </div>
                    
                    <div class="card-content">
                        <!-- Staff Limit -->
                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span class="staff-limit-text">Max Staff:</span>
                            <span class="staff-limit-value" id="regular-staff-limit">-</span>
                        </div>

                        <!-- Features List -->
                        <div class="features-container">
                            <h5><?php _e('Fitur Utama', 'wp-customer'); ?></h5>
                            <ul class="plan-features" id="regular-features">
                                <li class="loading-placeholder"><?php _e('Memuat data...', 'wp-customer'); ?></li>
                            </ul>
                        </div>

                        <!-- Trial Badge -->
                        <div class="trial-badge" id="regular-trial"></div>

                        <!-- Tombol Upgrade -->
                        <div class="upgrade-button-container" id="tombol-upgrade-regular"></div>
                    </div>
                </div>

                <!-- Priority Card -->
                <div class="upgrade-card" id="priority-card" data-level="priority">
                    <div class="card-header">
                        <h4 id="priority-name">Priority</h4>
                        <div class="price">
                            <span class="price-amount" id="priority-price">-</span>
                            <span class="price-period">/ bulan</span>
                        </div>
                        <div class="period-details"></div>
                    </div>
                    
                    <div class="card-content">
                        <!-- Staff Limit -->
                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span class="staff-limit-text">Max Staff:</span>
                            <span class="staff-limit-value" id="priority-staff-limit">-</span>
                        </div>

                        <!-- Features List -->
                        <div class="features-container">
                            <h5><?php _e('Fitur Utama', 'wp-customer'); ?></h5>
                            <ul class="plan-features" id="priority-features">
                                <li class="loading-placeholder"><?php _e('Memuat data...', 'wp-customer'); ?></li>
                            </ul>
                        </div>

                        <!-- Trial Badge -->
                        <div class="trial-badge" id="priority-trial"></div>

                        <!-- Tombol Upgrade -->
                        <div class="upgrade-button-container" id="tombol-upgrade-priority"></div>
                    </div>
                </div>

                <!-- Utama Card -->
                <div class="upgrade-card" id="utama-card" data-level="utama">
                    <div class="card-header">
                        <h4 id="utama-name">Utama</h4>
                        <div class="price">
                            <span class="price-amount" id="utama-price">-</span>
                            <span class="price-period">/ bulan</span>
                        </div>
                        <div class="period-details"></div>
                    </div>
                    
                    <div class="card-content">
                        <!-- Staff Limit -->
                        <div class="staff-limit">
                            <i class="dashicons dashicons-groups"></i>
                            <span class="staff-limit-text">Max Staff:</span>
                            <span class="staff-limit-value" id="utama-staff-limit">-</span>
                        </div>

                        <!-- Features List -->
                        <div class="features-container">
                            <h5><?php _e('Fitur Utama', 'wp-customer'); ?></h5>
                            <ul class="plan-features" id="utama-features">
                                <li class="loading-placeholder"><?php _e('Memuat data...', 'wp-customer'); ?></li>
                            </ul>
                        </div>

                        <!-- Trial Badge -->
                        <div class="trial-badge" id="utama-trial"></div>

                        <!-- Tombol Upgrade -->
                        <div class="upgrade-button-container" id="tombol-upgrade-utama"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
