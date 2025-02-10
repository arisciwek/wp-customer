<?php
/**
 * Membership Levels Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-membership.php
 *
 * Description: Template untuk mengelola membership levels
 *              Menampilkan dan mengelola level keanggotaan customer
 *              Includes form untuk edit dan tambah level baru
 *
 * Changelog:
 * 1.0.0 - 2024-01-10
 * - Initial version
 * - Added membership levels table
 * - Added management form
 */

if (!defined('ABSPATH')) {
    die;
}

// Get membership levels from database
global $wpdb;
$levels = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}app_customer_membership_levels 
    WHERE status = 'active'
    ORDER BY sort_order ASC
");

// Get all available features from database
$features = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}app_customer_membership_features
    WHERE status = 'active'
    ORDER BY field_group, sort_order ASC
");

// Group features by their type
$grouped_features = [];
foreach ($features as $feature) {
    if (!isset($grouped_features[$feature->field_group])) {
        $grouped_features[$feature->field_group] = [];
    }
    $grouped_features[$feature->field_group][] = $feature;
}
?>

<div class="wrap">
    <div class="membership-header">
        <h2><?php _e('Membership Levels Management', 'wp-customer'); ?></h2>
        <button type="button" class="button button-primary" id="add-membership-level">
            <?php _e('Add New Level', 'wp-customer'); ?>
        </button>
    </div>

    <div class="membership-grid">
        <?php foreach ($levels as $level): 
            $capabilities = json_decode($level->capabilities, true);
            ?>
            <div class="membership-card" data-level-id="<?php echo esc_attr($level->id); ?>">
                <div class="card-header">
                    <h3><?php echo esc_html($level->name); ?></h3>
                    <div class="actions">
                        <button type="button" class="button edit-level" data-id="<?php echo esc_attr($level->id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Basic Info -->
                    <div class="info-section">
                        <p class="description"><?php echo esc_html($level->description); ?></p>
                        <div class="price">
                            <?php echo number_format($level->price_per_month, 0, ',', '.'); ?> IDR/month
                        </div>
                    </div>

                    <!-- Staff & Department Limits -->
                    <div class="limits-section">
                        <div class="limit-item">
                            <label><?php _e('Max Staff:', 'wp-customer'); ?></label>
                            <span><?php echo $level->max_staff == -1 ? 'Unlimited' : $level->max_staff; ?></span>
                        </div>
                        <div class="limit-item">
                            <label><?php _e('Max Departments:', 'wp-customer'); ?></label>
                            <span><?php echo $level->max_departments == -1 ? 'Unlimited' : $level->max_departments; ?></span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="features-section">
                        <h4><?php _e('Features', 'wp-customer'); ?></h4>
                        <ul class="feature-list">
                            <?php 
                            if (isset($capabilities['features'])):
                                foreach ($grouped_features['features'] as $feature):
                                    $enabled = isset($capabilities['features'][$feature->field_name]) && 
                                             $capabilities['features'][$feature->field_name];
                                    if ($enabled):
                                        ?>
                                        <li class="feature-enabled">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php echo esc_html($feature->field_label); ?>
                                        </li>
                                        <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </ul>
                    </div>

                    <!-- Trial & Grace Period -->
                    <div class="period-section">
                        <?php if ($level->is_trial_available): ?>
                            <div class="period-item">
                                <label><?php _e('Trial Period:', 'wp-customer'); ?></label>
                                <span><?php echo esc_html($level->trial_days); ?> days</span>
                            </div>
                        <?php endif; ?>
                        <div class="period-item">
                            <label><?php _e('Grace Period:', 'wp-customer'); ?></label>
                            <span><?php echo esc_html($level->grace_period_days); ?> days</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Template for Add/Edit -->
<div id="membership-level-modal" class="wp-customer-modal" style="display:none;">
    <div class="modal-content">
        <h3 class="modal-title"></h3>
        <form id="membership-level-form">
            <input type="hidden" name="id" id="level-id">
            
            <div class="form-columns">
                <div class="form-column">
                    <!-- Basic Info Column -->
                    <div class="form-row">
                        <label for="level-name"><?php _e('Level Name', 'wp-customer'); ?></label>
                        <input type="text" id="level-name" name="name" required>
                    </div>

                    <div class="form-row">
                        <label for="level-price"><?php _e('Price per Month (IDR)', 'wp-customer'); ?></label>
                        <input type="number" id="level-price" name="price_per_month" min="0" required>
                    </div>

                    <div class="form-row">
                        <label for="max-staff"><?php _e('Max Staff', 'wp-customer'); ?></label>
                        <input type="number" id="max-staff" name="max_staff" min="-1" required>
                        <p class="description"><?php _e('-1 for unlimited', 'wp-customer'); ?></p>
                    </div>

                    <div class="form-row">
                        <label for="max-departments"><?php _e('Max Departments', 'wp-customer'); ?></label>
                        <input type="number" id="max-departments" name="max_departments" min="-1" required>
                        <p class="description"><?php _e('-1 for unlimited', 'wp-customer'); ?></p>
                    </div>
                </div>

                <div class="form-column">
                    <!-- Features Column -->
                    <div class="form-row">
                        <label><?php _e('Features', 'wp-customer'); ?></label>
                        <div class="feature-checkboxes">
                            <?php foreach ($grouped_features['features'] as $feature): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="features[<?php echo esc_attr($feature->field_name); ?>]" 
                                           value="1"
                                           class="<?php echo esc_attr($feature->css_class); ?>"
                                           id="<?php echo esc_attr($feature->css_id); ?>">
                                    <?php echo esc_html($feature->field_label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Trial Settings -->
                    <div class="form-row">
                        <label>
                            <input type="checkbox" id="is-trial-available" name="is_trial_available" value="1">
                            <?php _e('Enable Trial Period', 'wp-customer'); ?>
                        </label>
                    </div>

                    <div class="form-row trial-days-row" style="display:none;">
                        <label for="trial-days"><?php _e('Trial Days', 'wp-customer'); ?></label>
                        <input type="number" id="trial-days" name="trial_days" min="0">
                    </div>

                    <div class="form-row">
                        <label for="grace-period-days"><?php _e('Grace Period Days', 'wp-customer'); ?></label>
                        <input type="number" id="grace-period-days" name="grace_period_days" min="0" required>
                    </div>
                </div>

                <!-- Full Width Description -->
                <div class="form-row form-full-width">
                    <label for="level-description"><?php _e('Description', 'wp-customer'); ?></label>
                    <textarea id="level-description" name="description"></textarea>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Level', 'wp-customer'); ?>
                </button>
                <button type="button" class="button modal-close">
                    <?php _e('Cancel', 'wp-customer'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
