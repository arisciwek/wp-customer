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

$options = get_option('wp_customer_membership_settings', array());
?>

<form method="post" action="options.php">
    <?php settings_fields('wp_customer_membership_settings'); ?>
    
    <h3><?php _e('Level Regular', 'wp-customer'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Batas Staff', 'wp-customer'); ?></th>
            <td>
                <input type="number" 
                       name="wp_customer_membership_settings[regular_max_staff]" 
                       value="<?php echo esc_attr($options['regular_max_staff'] ?? 2); ?>"
                       min="-1"
                       class="small-text">
                <p class="description"><?php _e('-1 untuk unlimited', 'wp-customer'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Capabilities', 'wp-customer'); ?></th>
            <td>
                <?php
                $regular_caps = $options['regular_capabilities'] ?? array();
                ?>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[regular_capabilities][can_add_staff]" 
                           value="1"
                           <?php checked(isset($regular_caps['can_add_staff']) ? $regular_caps['can_add_staff'] : false); ?>>
                    <?php _e('Dapat menambah staff', 'wp-customer'); ?>
                </label><br>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[regular_capabilities][can_export]" 
                           value="1"
                           <?php checked(isset($regular_caps['can_export']) ? $regular_caps['can_export'] : false); ?>>
                    <?php _e('Dapat export data', 'wp-customer'); ?>
                </label>
            </td>
        </tr>
    </table>

    <h3><?php _e('Level Priority', 'wp-customer'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Batas Staff', 'wp-customer'); ?></th>
            <td>
                <input type="number" 
                       name="wp_customer_membership_settings[priority_max_staff]" 
                       value="<?php echo esc_attr($options['priority_max_staff'] ?? 5); ?>"
                       min="-1"
                       class="small-text">
                <p class="description"><?php _e('-1 untuk unlimited', 'wp-customer'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Capabilities', 'wp-customer'); ?></th>
            <td>
                <?php
                $priority_caps = $options['priority_capabilities'] ?? array();
                ?>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[priority_capabilities][can_add_staff]" 
                           value="1"
                           <?php checked(isset($priority_caps['can_add_staff']) ? $priority_caps['can_add_staff'] : false); ?>>
                    <?php _e('Dapat menambah staff', 'wp-customer'); ?>
                </label><br>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[priority_capabilities][can_export]" 
                           value="1"
                           <?php checked(isset($priority_caps['can_export']) ? $priority_caps['can_export'] : false); ?>>
                    <?php _e('Dapat export data', 'wp-customer'); ?>
                </label><br>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[priority_capabilities][can_bulk_import]" 
                           value="1"
                           <?php checked(isset($priority_caps['can_bulk_import']) ? $priority_caps['can_bulk_import'] : false); ?>>
                    <?php _e('Dapat bulk import', 'wp-customer'); ?>
                </label>
            </td>
        </tr>
    </table>

    <h3><?php _e('Level Utama', 'wp-customer'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Batas Staff', 'wp-customer'); ?></th>
            <td>
                <input type="number" 
                       name="wp_customer_membership_settings[utama_max_staff]" 
                       value="<?php echo esc_attr($options['utama_max_staff'] ?? -1); ?>"
                       min="-1"
                       class="small-text">
                <p class="description"><?php _e('-1 untuk unlimited', 'wp-customer'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Capabilities', 'wp-customer'); ?></th>
            <td>
                <?php
                $utama_caps = $options['utama_capabilities'] ?? array();
                ?>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[utama_capabilities][can_add_staff]" 
                           value="1"
                           <?php checked(isset($utama_caps['can_add_staff']) ? $utama_caps['can_add_staff'] : false); ?>>
                    <?php _e('Dapat menambah staff', 'wp-customer'); ?>
                </label><br>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[utama_capabilities][can_export]" 
                           value="1"
                           <?php checked(isset($utama_caps['can_export']) ? $utama_caps['can_export'] : false); ?>>
                    <?php _e('Dapat export data', 'wp-customer'); ?>
                </label><br>
                <label>
                    <input type="checkbox" 
                           name="wp_customer_membership_settings[utama_capabilities][can_bulk_import]" 
                           value="1"
                           <?php checked(isset($utama_caps['can_bulk_import']) ? $utama_caps['can_bulk_import'] : false); ?>>
                    <?php _e('Dapat bulk import', 'wp-customer'); ?>
                </label>
            </td>
        </tr>
    </table>

    <?php submit_button(__('Simpan Perubahan', 'wp-customer')); ?>
</form>

<script>
jQuery(document).ready(function($) {
    // Validasi form sebelum submit
    $('form').on('submit', function(e) {
        var isValid = true;
        
        // Validasi max staff
        $('input[type="number"]').each(function() {
            var value = parseInt($(this).val());
            if (value !== -1 && value < 1) {
                alert('Batas staff harus -1 atau minimal 1');
                isValid = false;
                return false;
            }
        });
        
        // Validasi capabilities
        $('.capabilities-group').each(function() {
            var hasCapability = false;
            $(this).find('input[type="checkbox"]').each(function() {
                if ($(this).is(':checked')) {
                    hasCapability = true;
                    return false;
                }
            });
            
            if (!hasCapability) {
                alert('Setiap level harus memiliki minimal satu capability');
                isValid = false;
                return false;
            }
        });
        
        return isValid;
    });
});
</script>
