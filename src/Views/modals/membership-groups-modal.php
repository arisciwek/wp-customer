<?php
/**
 * Membership Groups Modal Content
 * Path: /wp-customer/src/Views/modals/membership-groups-modal.php
 *
 * @package     WP_Customer
 * @subpackage  Views/Modals
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Modal content untuk manage membership groups
 *              Displays groups list dengan CRUD operations
 *
 * Changelog:
 * 1.0.0 - 2025-11-14 (Task-2205)
 * - Initial creation
 * - Groups table list
 * - Inline add/edit form
 */

if (!defined('ABSPATH')) {
    die;
}

// $groups data should be passed from controller
?>

<div id="membership-groups-modal-content">
    <!-- Toggle between list and form -->
    <div id="groups-list-view">
        <div class="groups-header">
            <h3><?php _e('Feature Groups', 'wp-customer'); ?></h3>
            <button type="button" class="button button-primary" id="show-group-form">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add New Group', 'wp-customer'); ?>
            </button>
        </div>

        <?php if (empty($groups)): ?>
            <!-- Empty State -->
            <div class="groups-empty-state">
                <span class="dashicons dashicons-category"></span>
                <p><?php _e('No groups found. Create your first group to organize membership features.', 'wp-customer'); ?></p>
                <button type="button" class="button button-primary" id="show-group-form-empty">
                    <?php _e('Add First Group', 'wp-customer'); ?>
                </button>
            </div>
        <?php else: ?>
            <!-- Groups Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="col-name"><?php _e('Name', 'wp-customer'); ?></th>
                        <th class="col-slug"><?php _e('Slug', 'wp-customer'); ?></th>
                        <th class="col-capability"><?php _e('Capability Group', 'wp-customer'); ?></th>
                        <th class="col-description"><?php _e('Description', 'wp-customer'); ?></th>
                        <th class="col-sort"><?php _e('Sort Order', 'wp-customer'); ?></th>
                        <th class="col-actions"><?php _e('Actions', 'wp-customer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr data-group-id="<?php echo esc_attr($group['id']); ?>">
                            <td><strong><?php echo esc_html($group['name']); ?></strong></td>
                            <td><code><?php echo esc_html($group['slug']); ?></code></td>
                            <td>
                                <span class="capability-badge capability-<?php echo esc_attr($group['capability_group']); ?>">
                                    <?php echo esc_html(ucfirst($group['capability_group'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($group['description'] ?: '-'); ?></td>
                            <td><?php echo esc_html($group['sort_order']); ?></td>
                            <td>
                                <button type="button"
                                        class="button button-small edit-group-btn"
                                        data-id="<?php echo esc_attr($group['id']); ?>"
                                        title="<?php esc_attr_e('Edit Group', 'wp-customer'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button"
                                        class="button button-small delete-group-btn"
                                        data-id="<?php echo esc_attr($group['id']); ?>"
                                        title="<?php esc_attr_e('Delete Group', 'wp-customer'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Form (hidden by default) -->
    <div id="group-form-view" style="display: none;">
        <div class="form-header">
            <h3>
                <span id="form-title"><?php _e('Add New Group', 'wp-customer'); ?></span>
            </h3>
            <button type="button" class="button" id="cancel-group-form">
                <?php _e('Back to List', 'wp-customer'); ?>
            </button>
        </div>

        <form id="membership-group-form">
            <input type="hidden" id="group-id" name="id" value="">

            <!-- Name -->
            <div class="form-row">
                <label for="group-name">
                    <?php _e('Group Name', 'wp-customer'); ?> <span class="required">*</span>
                </label>
                <input type="text"
                       id="group-name"
                       name="name"
                       class="regular-text"
                       required
                       maxlength="100"
                       placeholder="<?php esc_attr_e('e.g., Data Management', 'wp-customer'); ?>">
                <p class="description">
                    <?php _e('Display name for the group', 'wp-customer'); ?>
                </p>
            </div>

            <!-- Slug -->
            <div class="form-row">
                <label for="group-slug">
                    <?php _e('Slug', 'wp-customer'); ?> <span class="required">*</span>
                </label>
                <input type="text"
                       id="group-slug"
                       name="slug"
                       class="regular-text"
                       required
                       readonly
                       pattern="[a-z0-9\-]+"
                       placeholder="<?php esc_attr_e('e.g., data-management', 'wp-customer'); ?>"
                       style="background-color: #f0f0f1; cursor: not-allowed;">
                <p class="description">
                    <?php _e('Auto-generated from name (read-only)', 'wp-customer'); ?>
                </p>
            </div>

            <!-- Capability Group -->
            <div class="form-row">
                <label for="capability-group">
                    <?php _e('Capability Group', 'wp-customer'); ?> <span class="required">*</span>
                </label>
                <select id="capability-group" name="capability_group" class="regular-text" required>
                    <option value="features"><?php _e('Features', 'wp-customer'); ?></option>
                    <option value="limits"><?php _e('Limits', 'wp-customer'); ?></option>
                    <option value="notifications"><?php _e('Notifications', 'wp-customer'); ?></option>
                </select>
                <p class="description">
                    <?php _e('Category this group belongs to', 'wp-customer'); ?>
                </p>
            </div>

            <!-- Description -->
            <div class="form-row">
                <label for="group-description">
                    <?php _e('Description', 'wp-customer'); ?>
                </label>
                <textarea id="group-description"
                          name="description"
                          class="large-text"
                          rows="3"
                          placeholder="<?php esc_attr_e('Optional description for this group', 'wp-customer'); ?>"></textarea>
            </div>

            <!-- Sort Order -->
            <div class="form-row">
                <label for="sort-order">
                    <?php _e('Sort Order', 'wp-customer'); ?>
                </label>
                <input type="number"
                       id="sort-order"
                       name="sort_order"
                       class="small-text"
                       min="0"
                       value="0">
                <p class="description">
                    <?php _e('Display order (lower numbers appear first)', 'wp-customer'); ?>
                </p>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Group', 'wp-customer'); ?>
                </button>
                <button type="button" class="button" id="cancel-group-form-btn">
                    <?php _e('Cancel', 'wp-customer'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </form>
    </div>

    <!-- Loading State -->
    <div id="groups-loading" style="display: none;">
        <span class="spinner is-active"></span>
        <p><?php _e('Loading groups...', 'wp-customer'); ?></p>
    </div>
</div>
