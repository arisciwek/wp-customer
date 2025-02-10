<?php
/**
 * Membership Levels Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
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

// Prepare levels data with decoded capabilities
foreach ($levels as &$level) {
    $level->capabilities = json_decode($level->capabilities, true);
    $level->available_periods = json_decode($level->available_periods, true);
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
        <?php foreach ($levels as $level): ?>
            <div class="membership-card" data-level-id="<?php echo esc_attr($level->id); ?>">
                <!-- Card Header -->
                <div class="card-header">
                    <h3><?php echo esc_html($level->name); ?></h3>
                    <div class="price">
                        <?php echo number_format($level->price_per_month, 0, ',', '.'); ?> IDR/bulan
                    </div>
                </div>

                <!-- Card Content -->
                <div class="card-content">
                    <!-- Description -->
                    <div class="description">
                        <?php echo esc_html($level->description); ?>
                    </div>

                    <!-- Features Section -->
                    <div class="features-section">
                        <h4><?php _e('Features', 'wp-customer'); ?></h4>
                        <ul class="feature-list">
                            <?php foreach ($grouped_features['features'] as $feature): 
                                $is_enabled = !empty($level->capabilities['features'][$feature->field_name]);
                                if ($is_enabled):
                            ?>
                                <li class="feature-item feature-enabled">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php echo esc_html($feature->field_label); ?>
                                </li>
                            <?php endif; ?>
                            <?php endforeach; // Close the inner foreach loop ?>
                        </ul>
                    </div>

                    <!-- Limits Section -->
                    <div class="limits-section">
                        <?php foreach ($grouped_features['limits'] as $limit): 
                            $value = $level->capabilities['limits'][$limit->field_name] ?? 0;
                        ?>
                            <div class="limit-item">
                                <label><?php echo esc_html($limit->field_label); ?>:</label>
                                <span><?php echo $value == -1 ? __('Unlimited', 'wp-customer') : $value; ?></span>
                            </div>
                        <?php endforeach; // Close the limits foreach loop ?>
                    </div>

                    <!-- Notifications Section -->
                    <div class="notifications-section">
                        <h4><?php _e('Notifications', 'wp-customer'); ?></h4>
                        <ul class="notification-list">
                            <?php foreach ($grouped_features['notifications'] as $notification): 
                                $is_enabled = !empty($level->capabilities['notifications'][$notification->field_name]);
                                if ($is_enabled):
                            ?>
                                <li class="notification-item">
                                    <span class="dashicons dashicons-bell"></span>
                                    <?php echo esc_html($notification->field_label); ?>
                                </li>
                            <?php endif; ?>
                            <?php endforeach; // Close the notifications foreach loop ?>
                        </ul>
                    </div>

                    <!-- Trial & Grace Period -->
                    <div class="period-info">
                        <?php if ($level->is_trial_available): ?>
                            <div class="trial-period">
                                <span class="dashicons dashicons-clock"></span>
                                <?php printf(__('Trial Period: %d days', 'wp-customer'), $level->trial_days); ?>
                            </div>
                        <?php endif; ?>
                        <div class="grace-period">
                            <span class="dashicons dashicons-backup"></span>
                            <?php printf(__('Grace Period: %d days', 'wp-customer'), $level->grace_period_days); ?>
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                    <button type="button" class="button edit-level" data-id="<?php echo esc_attr($level->id); ?>">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Edit', 'wp-customer'); ?>
                    </button>
                    <button type="button" class="button delete-level" data-id="<?php echo esc_attr($level->id); ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete', 'wp-customer'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; // Close the outer foreach loop ?>
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
                        <input type="number" id="grace-period-days" name="grace_period_days" min="-1" required>
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

