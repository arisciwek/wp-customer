<?php
if (!defined('ABSPATH')) {
    die;
}
?>
<?php
if (!defined('ABSPATH')) {
    die;
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
            <div class="membership-card" data-level="<?php echo esc_attr($level['slug']); ?>">
                <!-- Header Section -->
                <div class="card-header">
                    <div class="level-name"><?php echo esc_html($level['name']); ?></div>
                    <div class="price">
                        Rp <?php echo number_format($level['price_per_month'], 0, ',', '.'); ?> /bulan
                    </div>
                    <div class="description"><?php echo esc_html($level['description']); ?></div>
                </div>

                <!-- Features Section -->
                <?php if (!empty($level['features'])): ?>
                    <div class="features-section">
                        <h4><?php _e('Features', 'wp-customer'); ?></h4>
                        <ul class="feature-list">
                            <?php foreach ($level['features'] as $key => $feature): ?>
                                <li class="feature-item <?php echo $feature['value'] ? 'enabled' : 'disabled'; ?>">
                                    <span class="dashicons <?php echo $feature['value'] ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span>
                                    <span class="feature-label"><?php echo esc_html($feature['label']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Limits Section -->
                <?php if (!empty($level['limits'])): ?>
                    <div class="limits-section">
                        <h4><?php _e('Resource Limits', 'wp-customer'); ?></h4>
                        <ul class="feature-list">
                            <?php foreach ($level['limits'] as $key => $limit): ?>
                                <li class="limit-item">
                                    <span class="limit-label"><?php echo esc_html($limit['label']); ?></span>
                                    <span class="limit-value">
                                        <?php echo $limit['value'] === -1 ? '∞' : esc_html($limit['value']); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Trial & Grace Period -->
                <div class="period-info">
                    <?php if ($level['is_trial_available']): ?>
                        <div class="trial-period">
                            <span class="dashicons dashicons-clock"></span>
                            <?php printf(__('Trial Period: %d days', 'wp-customer'), $level['trial_days']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($level['grace_period_days'] > 0): ?>
                        <div class="grace-period">
                            <span class="dashicons dashicons-backup"></span>
                            <?php printf(__('Grace Period: %d days', 'wp-customer'), $level['grace_period_days']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="card-actions">
                    <button type="button" class="button edit-level" data-id="<?php echo esc_attr($level['id']); ?>">
                        <?php _e('Edit', 'wp-customer'); ?>
                    </button>
                    <button type="button" class="button button-link-delete delete-level" data-id="<?php echo esc_attr($level['id']); ?>">
                        <?php _e('Delete', 'wp-customer'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Form (sisanya tetap sama) -->
<!-- Modal Form -->
<!-- Modal Form -->
<div id="membership-level-modal" class="wp-customer-modal">
    <div class="modal-content">
        <div class="modal-header clearfix">
            <h3 class="modal-title"></h3>
            <button type="button" class="modal-close dashicons dashicons-no-alt"></button>
        </div>
        
        <form id="membership-level-form" class="wp-customer-form clearfix">
            <?php wp_nonce_field('wp_customer_membership_level', 'membership_level_nonce'); ?>
            <input type="hidden" name="id" id="level-id">

            <div class="form-fields-wrapper">
                <!-- Left Side -->
                <div class="left-side">
                    <!-- Basic Info Section -->
                    <div class="form-section">
                        <h4><span class="dashicons dashicons-admin-generic"></span> <?php _e('Basic Information', 'wp-customer'); ?></h4>
                        
                        <div class="form-row">
                            <label for="level-name"><?php _e('Level Name', 'wp-customer'); ?> <span class="required">*</span></label>
                            <input type="text" id="level-name" name="name" class="regular-text" required>
                            <p class="description"><?php _e('Enter a unique name for this membership level', 'wp-customer'); ?></p>
                        </div>

                        <div class="form-row">
                            <label for="level-description"><?php _e('Description', 'wp-customer'); ?></label>
                            <textarea id="level-description" name="description" rows="3" class="large-text"></textarea>
                            <p class="description"><?php _e('Brief description of what this level offers', 'wp-customer'); ?></p>
                        </div>

                        <div class="form-row">
                            <label for="price-per-month"><?php _e('Price per Month (Rp)', 'wp-customer'); ?> <span class="required">*</span></label>
                            <input type="number" id="price-per-month" name="price_per_month" min="0" step="1000" class="regular-text" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="sort-order"><?php _e('Sort Order', 'wp-customer'); ?></label>
                            <input type="number" id="sort-order" name="sort_order" min="0" class="small-text">
                            <p class="description"><?php _e('Order in which this level appears (lower numbers first)', 'wp-customer'); ?></p>
                        </div>
                    </div>

                    <!-- Features Sections - Dynamically Generated -->
                    <?php
                    $features = $grouped_features;
                    foreach ($features as $group => $group_features):
                        // Skip 'resources' group as it will be handled separately
                        if ($group === 'resources') continue;
                    ?>
                        <div class="form-section">
                            <h4>
                                <span class="dashicons <?php echo esc_attr($group_features[0]['metadata']['ui_settings']['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                                <?php echo esc_html($group_features[0]['metadata']['label'] ?? ucfirst($group)); ?>
                            </h4>
                            
                            <div class="features-grid">
                                <?php foreach ($group_features as $feature): 
                                    $metadata = $feature['metadata'];
                                    $field_name = $feature['field_name'];
                                ?>
                                    <div class="feature-item">
                                        <label class="checkbox-label">
                                            <input type="<?php echo esc_attr($metadata['type']); ?>" 
                                                name="features[<?php echo esc_attr($field_name); ?>]"
                                                id="feature-<?php echo esc_attr($field_name); ?>"
                                                class="<?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>"
                                                <?php if ($metadata['is_required']): ?>required<?php endif; ?>>
                                            <span><?php echo esc_html($metadata['label']); ?></span>
                                            <?php if (!empty($metadata['description'])): ?>
                                                <p class="description"><?php echo esc_html($metadata['description']); ?></p>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Right Side -->
                <div class="right-side">
                    <!-- Resource Limits Section - Dynamically Generated -->
                    <?php if (isset($features['resources'])): ?>
                    <div class="form-section">
                        <h4>
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Resource Limits', 'wp-customer'); ?>
                        </h4>
                        <div class="limits-grid">
                            <?php foreach ($features['resources'] as $feature):
                                $metadata = $feature['metadata'];
                                $field_name = $feature['field_name'];
                            ?>
                                <div class="form-row">
                                    <label for="<?php echo esc_attr($field_name); ?>">
                                        <?php echo esc_html($metadata['label']); ?>
                                    </label>
                                    <input type="number" 
                                        id="<?php echo esc_attr($field_name); ?>"
                                        name="limits[<?php echo esc_attr($field_name); ?>]"
                                        min="<?php echo esc_attr($metadata['ui_settings']['min'] ?? -1); ?>"
                                        max="<?php echo esc_attr($metadata['ui_settings']['max'] ?? ''); ?>"
                                        step="<?php echo esc_attr($metadata['ui_settings']['step'] ?? 1); ?>"
                                        class="small-text <?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>"
                                        value="<?php echo esc_attr($metadata['default_value'] ?? 0); ?>"
                                        <?php if ($metadata['is_required']): ?>required<?php endif; ?>>
                                    <?php if (!empty($metadata['description'])): ?>
                                        <p class="description"><?php echo esc_html($metadata['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Trial & Grace Period Section -->
                    <div class="form-section">
                        <h4><span class="dashicons dashicons-clock"></span> <?php _e('Trial & Grace Period', 'wp-customer'); ?></h4>
                        
                        <div class="form-row">
                            <label class="checkbox-label">
                                <input type="checkbox" id="is-trial-available" name="is_trial_available">
                                <span><?php _e('Enable Trial Period', 'wp-customer'); ?></span>
                            </label>
                        </div>
                        
                        <div class="form-row trial-days-row" style="display:none;">
                            <label for="trial-days"><?php _e('Trial Period (Days)', 'wp-customer'); ?></label>
                            <input type="number" id="trial-days" name="trial_days" min="0" class="small-text" value="0">
                            <p class="description"><?php _e('Number of days for trial period', 'wp-customer'); ?></p>
                        </div>

                        <div class="form-row">
                            <label for="grace-period-days"><?php _e('Grace Period (Days)', 'wp-customer'); ?></label>
                            <input type="number" id="grace-period-days" name="grace_period_days" min="0" class="small-text" value="0">
                            <p class="description"><?php _e('Number of days before subscription is suspended', 'wp-customer'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer clearfix">
                <div class="modal-buttons">
                    <button type="button" class="button modal-close">
                        <?php _e('Cancel', 'wp-customer'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Level', 'wp-customer'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
