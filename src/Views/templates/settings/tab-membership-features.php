<?php
/**
 * Membership Features Tab Template
 * Path /wp-customer/src/Views/templates/settings/tab-membership-features.php
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.12
 *
 * Changelog:
 * 1.0.12 - 2025-01-13 (Task-2204)
 * - Changed to use data from MembershipFeaturesController & MembershipGroupsController
 * - Removed direct database query
 * - Data now passed from CustomerSettingsPageController::prepareViewData()
 */

if (!defined('ABSPATH')) {
    die;
}

// Field types yang tersedia
$field_types = ['checkbox', 'number', 'text'];
$field_subtypes = ['integer', 'float', 'text'];

// Data provided by CustomerSettingsPageController::prepareViewData()
// - $grouped_features : array dari MembershipFeaturesController::getAllFeatures()
// - $field_groups     : array dari MembershipGroupsController::getAllGroups()

// Cek apakah ada data yang dikirim dari controller
if (!isset($grouped_features) || !isset($field_groups)) {
    echo '<div class="notice notice-error"><p>';
    _e('Data tidak tersedia. Pastikan MembershipFeaturesController dan MembershipGroupsController berfungsi dengan baik.', 'wp-customer');
    echo '</p></div>';
    return;
}

// Hide default Save/Reset buttons for this AJAX-based tab
add_filter('wpc_settings_footer_content', function($footer_html, $current_tab) {
    if ($current_tab === 'membership-features') {
        // Return custom footer for membership-features tab
        ob_start();
        ?>
        <p class="submit" style="margin: 0;">
            <button type="button"
                    id="reset-membership-features-demo"
                    class="button button-secondary"
                    data-nonce="<?php echo wp_create_nonce('customer_generate_membership_features'); ?>">
                <?php _e('Reset Ke Default Data', 'wp-customer'); ?>
            </button>
            <span class="description" style="margin-left: 10px;">
                <?php _e('Hapus semua features & groups, kemudian generate ulang default data', 'wp-customer'); ?>
            </span>
        </p>
        <?php
        return ob_get_clean();
    }
    return $footer_html;
}, 10, 2);

?>
<div class="wrap">
    <div class="membership-features-header">
        <h2><?php _e('Membership Features Management', 'wp-customer'); ?></h2>
        <div class="header-actions">
            <button type="button" class="button button-secondary" id="manage-membership-groups">
                <span class="dashicons dashicons-category" style="margin-top: 3px;"></span>
                <?php _e('Manage Groups', 'wp-customer'); ?>
            </button>
            <button type="button" class="button button-primary" id="add-membership-feature">
                <?php _e('Add New Feature', 'wp-customer'); ?>
            </button>
        </div>
    </div>

    <!-- Features Table -->
    <div class="membership-features-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Group', 'wp-customer'); ?></th>
                    <th><?php _e('Name', 'wp-customer'); ?></th>
                    <th><?php _e('Label', 'wp-customer'); ?></th>
                    <th><?php _e('Type', 'wp-customer'); ?></th>
                    <th><?php _e('Required', 'wp-customer'); ?></th>
                    <th><?php _e('Sort Order', 'wp-customer'); ?></th>
                    <th><?php _e('Actions', 'wp-customer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($grouped_features)):
                    // $grouped_features structure from controller:
                    // [group_id => ['id' => ..., 'name' => ..., 'slug' => ..., 'features' => [...]]]
                    foreach ($grouped_features as $group_id => $group_data):
                        if (!empty($group_data['features'])):
                            foreach ($group_data['features'] as $feature):
                                $metadata = $feature['metadata']; // Already decoded by controller
                ?>
                    <tr>
                        <td><?php echo esc_html($group_data['name']); ?></td>
                        <td><?php echo esc_html($feature['field_name']); ?></td>
                        <td><?php echo esc_html($metadata['label'] ?? '-'); ?></td>
                        <td>
                            <?php
                            echo esc_html($metadata['type'] ?? '-');
                            if (isset($metadata['subtype'])) {
                                echo ' (' . esc_html($metadata['subtype']) . ')';
                            }
                            ?>
                        </td>
                        <td><?php echo !empty($metadata['is_required']) ? '✓' : '-'; ?></td>
                        <td><?php echo esc_html($feature['sort_order']); ?></td>
                        <td>
                            <button type="button"
                                    class="button edit-feature"
                                    data-id="<?php echo esc_attr($feature['id']); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button"
                                    class="button delete-feature"
                                    data-id="<?php echo esc_attr($feature['id']); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                <?php
                            endforeach;
                        endif;
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="7"><?php _e('No features found.', 'wp-customer'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Modal Form for Add/Edit Feature -->
<div id="membership-feature-modal" class="wp-customer-modal" style="display:none;">
    <div class="modal-content">
        <form id="membership-feature-form">
            <div class="modal-header">
                <div class="modal-title">
                    <span class="modal-icon"></span>
                    <h3 id="modal-title"></h3>
                </div>
                <button type="button" class="modal-close" aria-label="Tutup modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id" id="feature-id">
                
                <!-- Feature Group -->
                <div class="form-row">
                    <label for="field-group" class="required-field">
                        <?php _e('Grup Fitur', 'wp-customer'); ?>
                    </label>
                    <select id="field-group" name="field_group" required>
                        <?php
                        // $field_groups structure from controller:
                        // [['id' => ..., 'name' => ..., 'slug' => ..., ...], ...]
                        foreach ($field_groups as $group):
                        ?>
                            <option value="<?php echo esc_attr($group['id']); ?>">
                                <?php echo esc_html($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Field Name -->
                <div class="form-row">
                    <label for="field-name" class="required-field">
                        <?php _e('Nama Field', 'wp-customer'); ?>
                    </label>
                    <input type="text" 
                           id="field-name" 
                           name="field_name" 
                           required 
                           pattern="[a-z_]+" 
                           title="Hanya huruf kecil dan underscore diperbolehkan">
                    <p class="description">
                        <?php _e('Identifier unik (contoh: can_add_staff, max_departments)', 'wp-customer'); ?>
                    </p>
                </div>

                <!-- Field Label -->
                <div class="form-row">
                    <label for="field-label" class="required-field">
                        <?php _e('Label Field', 'wp-customer'); ?>
                    </label>
                    <input type="text" id="field-label" name="field_label" required>
                    <p class="description">
                        <?php _e('Label yang ditampilkan (contoh: "Dapat Menambah Staff")', 'wp-customer'); ?>
                    </p>
                </div>

                <!-- Field Type -->
                <div class="form-row">
                    <label for="field-type" class="required-field">
                        <?php _e('Tipe Field', 'wp-customer'); ?>
                    </label>
                    <select id="field-type" name="field_type" required>
                            <?php foreach ($field_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>">
                                    <?php echo esc_html(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Field Subtype -->
                    <div class="form-row field-subtype-row" style="display:none;">
                        <label for="field-subtype">
                            <?php _e('Subtipe Field', 'wp-customer'); ?>
                        </label>
                        <select id="field-subtype" name="field_subtype">
                            <option value=""><?php _e('Tidak Ada', 'wp-customer'); ?></option>
                            <?php foreach ($field_subtypes as $subtype): ?>
                                <option value="<?php echo esc_attr($subtype); ?>">
                                    <?php echo esc_html(ucfirst($subtype)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Required Field Checkbox -->
                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="is_required" value="1">
                            <?php _e('Field Wajib Diisi', 'wp-customer'); ?>
                        </label>
                    </div>

                    <!-- CSS Class -->
                    <div class="form-row">
                        <label for="css-class">
                            <?php _e('CSS Class', 'wp-customer'); ?>
                        </label>
                        <input type="text" id="css-class" name="css_class">
                    </div>

                    <!-- CSS ID -->
                    <div class="form-row">
                        <label for="css-id">
                            <?php _e('CSS ID', 'wp-customer'); ?>
                        </label>
                        <input type="text" id="css-id" name="css_id">
                    </div>

                    <!-- Sort Order -->
                    <div class="form-row">
                        <label for="sort-order">
                            <?php _e('Urutan', 'wp-customer'); ?>
                        </label>
                        <input type="number" 
                               id="sort-order" 
                               name="sort_order" 
                               min="0" 
                               value="0">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="submit" class="button button-primary">
                        <?php _e('Simpan Fitur', 'wp-customer'); ?>
                    </button>
                    <button type="button" class="button modal-close">
                        <?php _e('Batal', 'wp-customer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </form>
    </div>
</div>
