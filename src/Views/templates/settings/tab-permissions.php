<?php
/**
 * Permission Management Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola hak akses plugin WP Customer
 *              Menampilkan matrix permission untuk setiap role
 *              Hanya menampilkan customer roles (bukan semua WordPress roles)
 *
 * Changelog:
 * v1.1.0 - 2025-10-29 (TODO-2181)
 * - BREAKING: Show only customer roles (not all WordPress roles)
 * - Added: Header section with description
 * - Added: Icon indicator for customer roles
 * - Improved: Section styling following wp-app-core pattern
 * - Changed: Better descriptions and info messages
 *
 * v1.0.0 - 2024-01-07
 * - Initial version
 * - Add permission matrix
 * - Add role management
 * - Add tooltips for permissions
 */

if (!defined('ABSPATH')) {
    die;
}

// Get permission model instance
$permission_model = new \WPCustomer\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();
$capability_descriptions = $permission_model->getCapabilityDescriptions();

// Load RoleManager
require_once WP_CUSTOMER_PATH . 'includes/class-role-manager.php';

// DEBUG LOG - START
error_log('=== WP-CUSTOMER PERMISSION TAB DEBUG ===');
error_log('File loaded: tab-permissions.php v1.1.0');
error_log('WP_CUSTOMER_PATH: ' . WP_CUSTOMER_PATH);

// Get customer roles
$customer_roles = WP_Customer_Role_Manager::getRoleSlugs();
error_log('Customer roles from RoleManager: ' . print_r($customer_roles, true));

$existing_customer_roles = [];
foreach ($customer_roles as $role_slug) {
    if (WP_Customer_Role_Manager::roleExists($role_slug)) {
        $existing_customer_roles[] = $role_slug;
        error_log('Role exists: ' . $role_slug);
    } else {
        error_log('Role NOT exists: ' . $role_slug);
    }
}
$customer_roles_exist = !empty($existing_customer_roles);
error_log('Customer roles exist: ' . ($customer_roles_exist ? 'YES' : 'NO'));
error_log('Existing customer roles count: ' . count($existing_customer_roles));

// Get all editable roles
$all_roles = get_editable_roles();
error_log('Total editable roles in WP: ' . count($all_roles));
error_log('All role names: ' . implode(', ', array_keys($all_roles)));

// Display ONLY customer roles (exclude other plugin roles and standard WP roles)
// Customer permissions are specifically for customer management
// Exclude base role 'customer' to avoid confusion (it only has 'read' capability)
$displayed_roles = [];
if ($customer_roles_exist) {
    // Show only customer roles with the dashicons-groups icon indicator
    // Skip base role 'customer' - it's for dual-role pattern, not for direct assignment
    foreach ($existing_customer_roles as $role_slug) {
        // Skip base role 'customer'
        if ($role_slug === 'customer') {
            error_log('Skipping base role: customer (dual-role pattern)');
            continue;
        }

        if (isset($all_roles[$role_slug])) {
            $displayed_roles[$role_slug] = $all_roles[$role_slug];
            error_log('Added to displayed_roles: ' . $role_slug);
        }
    }
}
error_log('Total displayed roles: ' . count($displayed_roles));
error_log('Displayed role names: ' . implode(', ', array_keys($displayed_roles)));
error_log('=== DEBUG END ===');
// DEBUG LOG - END

// Get current active tab with validation
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'customer';

// Validate that the tab exists in capability_groups, fallback to 'customer' if not
if (!isset($capability_groups[$current_tab])) {
    $current_tab = 'customer';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    if (!check_admin_referer('wp_customer_permissions')) {
        wp_die(__('Security check failed.', 'wp-customer'));
    }

    $current_tab = sanitize_key($_POST['current_tab']);

    // Need to get capability groups for form processing
    $temp_capability_groups = $permission_model->getCapabilityGroups();

    // Only get capabilities for current tab
    $current_tab_caps = isset($temp_capability_groups[$current_tab]['caps']) ?
                       $temp_capability_groups[$current_tab]['caps'] :
                       [];

    $updated = false;

    // Only process customer roles (consistent with display filter)
    $temp_customer_roles = WP_Customer_Role_Manager::getRoleSlugs();
    foreach ($temp_customer_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            // Only process capabilities from current tab
            foreach ($current_tab_caps as $cap) {
                $has_cap = isset($_POST['permissions'][$role_name][$cap]);
                if ($role->has_cap($cap) !== $has_cap) {
                    if ($has_cap) {
                        $role->add_cap($cap);
                    } else {
                        $role->remove_cap($cap);
                    }
                    $updated = true;
                }
            }
        }
    }

    if ($updated) {
        add_settings_error(
            'wp_customer_messages',
            'permissions_updated',
            sprintf(
                __('Permission settings for %s have been successfully updated.', 'wp-customer'),
                '<strong>' . esc_html($temp_capability_groups[$current_tab]['title']) . '</strong>'
            ),
            'success'
        );
    }
}

// Handle reset success notice
if (isset($_GET['permissions-reset']) && $_GET['permissions-reset'] === '1') {
    // Clear any old settings errors EXCEPT our plugin messages
    global $wp_settings_errors;
    if (isset($wp_settings_errors)) {
        $wp_settings_errors = array_filter($wp_settings_errors, function($error) {
            return $error['setting'] === 'wp_customer_messages';
        });
    }

    add_settings_error(
        'wp_customer_messages',
        'permissions_reset',
        __('Hak akses Customer berhasil direset ke pengaturan default.', 'wp-customer'),
        'success'
    );
}
?>

<div class="wrap">
    <!-- DEBUG: tab-permissions.php v1.1.0 loaded -->
    <!-- DEBUG: Total displayed roles: <?php echo count($displayed_roles); ?> -->
    <!-- DEBUG: Customer roles exist: <?php echo $customer_roles_exist ? 'YES' : 'NO'; ?> -->

    <div>
        <?php settings_errors('wp_customer_messages'); ?>
    </div>

    <h2 class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($capability_groups as $tab_key => $group): ?>
            <a href="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $tab_key]); ?>"
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
               title="<?php echo esc_attr($group['description'] ?? $group['title']); ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <!-- Header Section -->
    <div class="settings-header-section" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px 20px; margin-top: 20px; border-radius: 4px;">
        <h3 style="margin: 0; color: #1d2327; font-size: 16px;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 20px; vertical-align: middle; margin-right: 8px;"></span>
            <?php
            printf(
                __('Managing %s Permissions', 'wp-customer'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
            <span style="background: #2271b1; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 3px; margin-left: 10px; font-weight: normal;">v1.1.0</span>
        </h3>
        <p style="margin: 8px 0 0 0; color: #646970; font-size: 13px; line-height: 1.6;">
            <?php _e('Configure which customer roles <span class="dashicons dashicons-groups" style="font-size: 14px; vertical-align: middle; color: #0073aa;"></span> have access to these capabilities. Only customer staff roles are shown here.', 'wp-customer'); ?>
        </p>
        <!-- DEBUG INFO -->
        <p style="margin: 8px 0 0 0; color: #d63638; font-size: 12px; font-family: monospace;">
            🔍 Debug: Displaying <?php echo count($displayed_roles); ?> customer role(s) | Customer roles exist: <?php echo $customer_roles_exist ? 'YES' : 'NO'; ?>
        </p>
    </div>

    <!-- Reset Section -->
    <div class="settings-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <button type="button" id="reset-permissions-btn" class="button button-secondary button-reset-permissions">
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e('Reset to Default', 'wp-customer'); ?>
        </button>
        <p class="description">
            <?php
            printf(
                __('Reset <strong>%s</strong> permissions to plugin defaults. This will restore the original capability settings for all roles in this group.', 'wp-customer'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </p>
    </div>

    <!-- Permission Matrix Section -->
    <div class="permissions-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #dcdcde;">
            <?php
            printf(
                __('Customer Settings - %s', 'wp-customer'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </h2>

        <form method="post" id="wp-customer-permissions-form" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>">
            <?php wp_nonce_field('wp_customer_permissions'); ?>
            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <input type="hidden" name="action" value="update_role_permissions">

            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Check capabilities for each customer role. WordPress Administrators automatically have full access to all customer capabilities.', 'wp-customer'); ?>
            </p>

            <table class="widefat fixed striped permission-matrix-table">
                <thead>
                    <tr>
                        <th class="column-role"><?php _e('Role', 'wp-customer'); ?></th>
                        <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                            <th class="column-permission">
                                <?php echo esc_html($permission_labels[$cap]); ?>
                                <span class="dashicons dashicons-info"
                                      title="<?php echo esc_attr($capability_descriptions[$cap] ?? ''); ?>">
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($displayed_roles)) {
                        echo '<tr><td colspan="' . (count($capability_groups[$current_tab]['caps']) + 1) . '" style="text-align:center;">';
                        _e('Tidak ada customer roles yang tersedia. Silakan buat customer roles terlebih dahulu.', 'wp-customer');
                        echo '</td></tr>';
                    } else {
                        foreach ($displayed_roles as $role_name => $role_info):
                            $role = get_role($role_name);
                            if (!$role) continue;
                    ?>
                        <tr>
                            <td class="column-role">
                                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                                <span class="dashicons dashicons-groups" style="color: #0073aa; font-size: 14px; vertical-align: middle;" title="<?php _e('Customer Role', 'wp-customer'); ?>"></span>
                            </td>
                            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                                <td class="column-permission">
                                    <input type="checkbox"
                                           class="permission-checkbox"
                                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]"
                                           value="1"
                                           data-role="<?php echo esc_attr($role_name); ?>"
                                           data-capability="<?php echo esc_attr($cap); ?>"
                                           <?php checked($role->has_cap($cap)); ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php
                        endforeach;
                    }
                    ?>
                </tbody>
            </table>
        </form>

        <!-- Sticky Footer with Action Buttons -->
        <div class="settings-footer">
            <p class="submit">
                <?php submit_button(__('Save Permission Changes', 'wp-customer'), 'primary', 'submit', false, ['form' => 'wp-customer-permissions-form']); ?>
            </p>
        </div>
    </div><!-- .permissions-section -->
</div><!-- .wrap -->

<!-- Modal Templates -->
<?php
if (function_exists('wp_customer_render_confirmation_modal')) {
    wp_customer_render_confirmation_modal();
}
