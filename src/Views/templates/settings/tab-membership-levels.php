<?php
/**
 * Membership Levels Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.1.0
 */

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
            <div class="membership-card" data-level="<?php echo esc_attr($level->slug); ?>">
                <!-- Card Header -->
                <div class="card-header">
                    <h3><?php echo esc_html($level->name); ?></h3>
                    <div class="price">
                        <?php echo number_format($level->price_per_month, 0, ',', '.'); ?> IDR/bulan
                    </div>
                    <p class="description"><?php echo esc_html($level->description); ?></p>
                </div>

                <!-- Card Content -->
                <div class="card-content">
                    <?php 
                    $capabilities = json_decode($level->capabilities, true);
                    
                    // Group features berdasarkan metadata
                    $grouped_features = [];
                    foreach ($features as $feature) {
                        $metadata = json_decode($feature->metadata, true);
                        $group = $metadata['group'];
                        
                        if (!isset($grouped_features[$group])) {
                            $grouped_features[$group] = [];
                        }
                        
                        $grouped_features[$group][] = [
                            'field_name' => $feature->field_name,
                            'metadata' => $metadata,
                            'value' => $capabilities[$feature->field_name] ?? null
                        ];
                    }

                    // Tampilkan fitur berdasarkan group
                    foreach ($grouped_features as $group => $group_features): ?>
                        <div class="feature-section" data-group="<?php echo esc_attr($group); ?>">
                            <h4><?php echo esc_html(ucfirst($group)); ?></h4>
                            
                            <div class="feature-list">
                                <?php foreach ($group_features as $feature): 
                                    $metadata = $feature['metadata'];
                                    $value = $feature['value'];
                                    
                                    switch ($metadata['type']):
                                        case 'checkbox': ?>
                                            <?php if ($value): ?>
                                                <div class="feature-item feature-enabled">
                                                    <?php if (!empty($metadata['ui_settings']['icon'])): ?>
                                                        <span class="dashicons <?php echo esc_attr($metadata['ui_settings']['icon']); ?>"></span>
                                                    <?php endif; ?>
                                                    <?php echo esc_html($metadata['label']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php break;
                                            
                                        case 'number': ?>
                                            <div class="feature-item">
                                                <span class="feature-label"><?php echo esc_html($metadata['label']); ?>:</span>
                                                <span class="feature-value">
                                                    <?php 
                                                    if ($value === -1) {
                                                        _e('Unlimited', 'wp-customer');
                                                    } else {
                                                        echo esc_html($value);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            <?php break;
                                    endswitch;
                                endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Period Info -->
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

                <!-- Card Actions -->
                <div class="card-actions">
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
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Form -->
<div id="membership-level-modal" class="wp-customer-modal" style="display:none;">
    <div class="modal-content">
        <h3 class="modal-title"></h3>
        <form id="membership-level-form">
            <input type="hidden" name="id" id="level-id">
            
            <div class="form-columns">
                <div class="form-column">
                    <!-- Basic Info -->
                    <div class="form-row">
                        <label for="level-name"><?php _e('Level Name', 'wp-customer'); ?></label>
                        <input type="text" id="level-name" name="name" required>
                    </div>

                    <div class="form-row">
                        <label for="level-price"><?php _e('Price per Month (IDR)', 'wp-customer'); ?></label>
                        <input type="number" id="level-price" name="price_per_month" min="0" required>
                    </div>

                    <div class="form-row">
                        <label for="sort-order"><?php _e('Sort Order', 'wp-customer'); ?></label>
                        <input type="number" id="sort-order" name="sort_order" min="0" required>
                    </div>
                </div>

                <div class="form-column">
                    <!-- Dynamic Features dari Metadata -->
                    <?php foreach ($grouped_features as $group => $group_features): ?>
                        <div class="feature-group" data-group="<?php echo esc_attr($group); ?>">
                            <h4><?php echo esc_html(ucfirst($group)); ?></h4>
                            
                            <?php foreach ($group_features as $feature): 
                                $metadata = $feature['metadata'];
                                switch ($metadata['type']):
                                    case 'checkbox': ?>
                                        <div class="form-row">
                                            <label class="<?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>">
                                                <input type="checkbox" 
                                                       name="features[<?php echo esc_attr($feature['field_name']); ?>]" 
                                                       value="1">
                                                <?php echo esc_html($metadata['label']); ?>
                                            </label>
                                            <?php if (!empty($metadata['description'])): ?>
                                                <p class="description"><?php echo esc_html($metadata['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php break;

                                    case 'number': 
                                        $min = $metadata['ui_settings']['min'] ?? 0;
                                        $max = $metadata['ui_settings']['max'] ?? '';
                                        $step = $metadata['ui_settings']['step'] ?? 1;
                                        ?>
                                        <div class="form-row">
                                            <label>
                                                <?php echo esc_html($metadata['label']); ?>
                                                <input type="number" 
                                                       name="limits[<?php echo esc_attr($feature['field_name']); ?>]"
                                                       class="<?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>"
                                                       min="<?php echo esc_attr($min); ?>"
                                                       <?php echo $max ? 'max="' . esc_attr($max) . '"' : ''; ?>
                                                       step="<?php echo esc_attr($step); ?>"
                                                       required>
                                            </label>
                                            <?php if (!empty($metadata['description'])): ?>
                                                <p class="description"><?php echo esc_html($metadata['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php break;
                                endswitch;
                            endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Trial & Grace Period -->
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

                <!-- Description -->
                <div class="form-row full-width">
                    <label for="level-description"><?php _e('Description', 'wp-customer'); ?></label>
                    <textarea id="level-description" name="description" required></textarea>
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
