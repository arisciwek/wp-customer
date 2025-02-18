<?php
/**
 * Permission Management Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola hak akses plugin WP Customer
 *              Menampilkan matrix permission untuk setiap role
 *
 * Changelog:
 * v1.0.0 - 2024-01-07
 * - Initial version
 * - Add permission matrix
 * - Add role management
 * - Add tooltips for permissions
 */

if (!defined('ABSPATH')) {
    die;
}

function get_capability_description($capability) {
    $descriptions = [
        // Customer capabilities
        'view_customer_list' => __('Memungkinkan melihat daftar semua customer dalam format tabel', 'wp-customer'),
        'view_customer_detail' => __('Memungkinkan melihat detail informasi customer', 'wp-customer'),
        'view_own_customer' => __('Memungkinkan melihat customer yang ditugaskan ke pengguna', 'wp-customer'),
        'add_customer' => __('Memungkinkan menambahkan data customer baru', 'wp-customer'),
        'edit_all_customers' => __('Memungkinkan mengedit semua data customer', 'wp-customer'),
        'edit_own_customer' => __('Memungkinkan mengedit hanya customer yang ditugaskan', 'wp-customer'),
        'delete_customer' => __('Memungkinkan menghapus data customer', 'wp-customer'),
        
        // Branch capabilities
        'view_branch_list' => __('Memungkinkan melihat daftar semua cabang', 'wp-customer'),
        'view_branch_detail' => __('Memungkinkan melihat detail informasi cabang', 'wp-customer'),
        'view_own_branch' => __('Memungkinkan melihat cabang yang ditugaskan', 'wp-customer'),
        'add_branch' => __('Memungkinkan menambahkan data cabang baru', 'wp-customer'),
        'edit_all_branches' => __('Memungkinkan mengedit semua data cabang', 'wp-customer'),
        'edit_own_branch' => __('Memungkinkan mengedit hanya cabang yang ditugaskan', 'wp-customer'),
        'delete_branch' => __('Memungkinkan menghapus data cabang', 'wp-customer'),

        // Employee capabilities
        'view_employee_list' => __('Memungkinkan melihat daftar semua karyawan', 'wp-customer'),
        'view_employee_detail' => __('Memungkinkan melihat detail informasi karyawan', 'wp-customer'),
        'view_own_employee' => __('Memungkinkan melihat karyawan yang ditugaskan', 'wp-customer'),
        'add_employee' => __('Memungkinkan menambahkan data karyawan baru', 'wp-customer'),
        'edit_all_employees' => __('Memungkinkan mengedit semua data karyawan', 'wp-customer'),
        'edit_own_employee' => __('Memungkinkan mengedit hanya karyawan yang ditugaskan', 'wp-customer'),
        'delete_employee' => __('Memungkinkan menghapus data karyawan', 'wp-customer')
    ];

    return isset($descriptions[$capability]) ? $descriptions[$capability] : '';
}

// Get permission model instance 
$permission_model = new \WPCustomer\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();
$all_roles = get_editable_roles();

// Get current active tab
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'customer';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    if (!check_admin_referer('wp_customer_permissions')) {
        wp_die(__('Security check failed.', 'wp-customer'));
    }

    $current_tab = sanitize_key($_POST['current_tab']);
    $capability_groups = $permission_model->getCapabilityGroups();
    
    // Only get capabilities for current tab
    $current_tab_caps = isset($capability_groups[$current_tab]['caps']) ? 
                       $capability_groups[$current_tab]['caps'] : 
                       [];

    $updated = false;
    foreach ($all_roles as $role_name => $role_info) {
        if ($role_name === 'administrator') {
            continue;
        }

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
                __('Hak akses %s berhasil diperbarui.', 'wp-customer'),
                $capability_groups[$current_tab]['title']
            ), 
            'success'
        );
    }
}
?>

<div class="wrap">
    <div>
        <?php settings_errors('wp_customer_messages'); ?>
    </div>

    <h2 class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($capability_groups as $tab_key => $group): ?>
            <a href="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $tab_key]); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="permissions-section">
        <!-- Add reset button section before the form -->
        <div class="reset-permissions-section">
            <form id="wp-customer-permissions-form" method="post" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>" id="reset-permissions-form">
                <?php wp_nonce_field('wp_customer_reset_permissions', 'reset_permissions_nonce'); ?>
                <input type="hidden" name="action" value="reset_permissions">
                <button type="button" id="reset-permissions-btn" class="button button-secondary">
                    <i class="dashicons dashicons-image-rotate"></i>
                    <?php _e('Reset to Default', 'wp-customer'); ?>
                </button>
            </form>
            <p class="description">
                <?php _e('Reset permissions to plugin defaults. This will restore the original capability settings for all roles.', 'wp-customer'); ?>
            </p>
        </div>

        <form id="wp-customer-permissions-form" method="post" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>">
            <?php wp_nonce_field('wp_customer_permissions'); ?>
            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <input type="hidden" name="action" value="update_role_permissions">

            <p class="description">
                <?php _e('Configure role permissions for managing customer data. Administrators automatically have full access.', 'wp-customer'); ?>
            </p>

            <table class="widefat fixed striped permissions-matrix">
                <thead>
                    <tr>
                        <th class="column-role"><?php _e('Role', 'wp-customer'); ?></th>
                        <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                            <th class="column-permission">
                                <?php echo esc_html($permission_labels[$cap]); ?>
                                <span class="dashicons dashicons-info tooltip-icon" 
                                      title="<?php echo esc_attr(get_capability_description($cap)); ?>">
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($all_roles as $role_name => $role_info):
                        if ($role_name === 'administrator') continue;
                        $role = get_role($role_name);
                    ?>
                        <tr>
                            <td class="column-role">
                                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                            </td>
                            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                                <td class="column-permission">
                                    <input type="checkbox" 
                                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]" 
                                           value="1"
                                           <?php checked($role->has_cap($cap)); ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(__('Save Changes', 'wp-customer')); ?>
        </form>
    </div>
</div>

<!-- Modal Templates -->
<?php
if (function_exists('wp_customer_render_confirmation_modal')) {
    wp_customer_render_confirmation_modal();
}
