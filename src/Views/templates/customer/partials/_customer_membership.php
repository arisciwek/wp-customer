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
    <!-- Loading State -->
    <div class="membership-loading-state" style="display: none;">
        <span class="spinner is-active"></span>
        <p><?php _e('Memuat data membership...', 'wp-customer'); ?></p>
    </div>

    <!-- Current Membership Status -->
    <div class="postbox membership-status-card">
        <h3 class="hndle">
            <span class="dashicons dashicons-buddicons-groups"></span>
            <?php _e('Status Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <!-- Status Badge -->
            <div class="membership-status-header">
                <span id="membership-status" class="status-badge"></span>
                <span id="membership-level-name" class="level-name"></span>
            </div>

            <!-- Staff Usage Section -->
            <div class="staff-usage-section">
                <h4><?php _e('Penggunaan Staff', 'wp-customer'); ?></h4>
                <div class="staff-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="staff-usage-bar"></div>
                    </div>
                    <div class="usage-text">
                        <span id="staff-usage-count"></span> / 
                        <span id="staff-usage-limit"></span> 
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
                        <td><span id="membership-start-date"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Berakhir', 'wp-customer'); ?></th>
                        <td><span id="membership-end-date"></span></td>
                    </tr>
                    <tr id="trial-info-row" style="display: none;">
                        <th><?php _e('Trial Berakhir', 'wp-customer'); ?></th>
                        <td><span id="trial-end-date"></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <?php if ($data['can_upgrade']): ?>
    <!-- Upgrade Section -->
    <div class="postbox upgrade-section">
        <h3 class="hndle">
            <span class="dashicons dashicons-upload"></span>
            <?php _e('Upgrade Membership', 'wp-customer'); ?>
        </h3>
        <div class="inside">
            <!-- Upgrade Cards Container -->
            <div class="upgrade-cards-container">
                <?php foreach ($data['available_levels'] as $level): ?>
                    <?php if ($level->id != $data['current_level']?->id): ?>
                        <div class="upgrade-card">
                            <h4><?php echo esc_html($level->name); ?></h4>
                            <div class="card-content">
                                <ul class="plan-features">
                                    <li>
                                        <?php 
                                        echo $level->max_staff === -1 
                                            ? __('Unlimited staff', 'wp-customer')
                                            : sprintf(__('Maksimal %d staff', 'wp-customer'), $level->max_staff);
                                        ?>
                                    </li>
                                    <?php 
                                    $capabilities = json_decode($level->capabilities, true);
                                    foreach ($capabilities['features'] as $cap => $enabled):
                                        if ($enabled):
                                    ?>
                                        <li><?php echo esc_html($this->getCapabilityLabel($cap)); ?></li>
                                    <?php 
                                        endif;
                                    endforeach;
                                    ?>
                                </ul>

                                <?php if ($level->trial_info['has_trial']): ?>
                                <div class="trial-badge">
                                    <?php printf(
                                        __('Free %d day trial', 'wp-customer'),
                                        $level->trial_info['trial_days']
                                    ); ?>
                                </div>
                                <?php endif; ?>

                                <div class="upgrade-price">
                                    <span class="price-amount">
                                        <?php echo number_format($level->price_per_month, 0, ',', '.'); ?>
                                    </span>
                                    <span class="price-period">
                                        <?php _e('/ bulan', 'wp-customer'); ?>
                                    </span>
                                </div>

                                <button type="button" 
                                        class="button button-primary upgrade-button" 
                                        data-level-id="<?php echo esc_attr($level->id); ?>">
                                    <?php printf(
                                        __('Upgrade ke %s', 'wp-customer'),
                                        esc_html($level->name)
                                    ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error State -->
    <div class="membership-error-state" style="display: none;">
        <div class="error-state-content">
            <span class="dashicons dashicons-warning"></span>
            <h4><?php _e('Gagal Memuat Data', 'wp-customer'); ?></h4>
            <p><?php _e('Terjadi kesalahan saat memuat data membership. Silakan coba lagi.', 'wp-customer'); ?></p>
            <button type="button" class="button reload-membership">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Muat Ulang', 'wp-customer'); ?>
            </button>
        </div>
    </div>
</div>

<?php
// Include any required modals or additional templates
//require_once WP_CUSTOMER_PATH . 'src/Views/templates/customer/forms/confirm-upgrade-modal.php';
?>
