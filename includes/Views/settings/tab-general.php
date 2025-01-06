<?php
/**
 * Customer Management General Settings Tab
 *
 * @package     CustomerManagement
 * @subpackage  Core/Views/Settings/Tabs
 * @version     1.0.0
 * @author      Your Name
 * @copyright   2024 Your Organization
 * @license     GPL-2.0+
 * 
 * Description:
 * Handles general plugin settings including:
 * - DataTables configuration
 * - Cache settings
 * - Debug mode
 * - Default display options
 * 
 * Path: includes/Views/settings/tab-general.php
 * Timestamp: 2024-01-06 11:00:00
 * 
 * Required Capabilities:
 * - manage_customer_settings
 * 
 * Dependencies:
 * - WordPress Settings API
 * - Customer Management Settings System
 * 
 * Changelog:
 * 1.0.0 - 2024-01-06
 * - Initial release
 * - Added DataTables settings
 * - Added cache configuration
 * - Added debug mode toggle
 */

if (!defined('ABSPATH')) {
    die;
}


// Get saved options with defaults
$options = get_option('wp_customer_settings', array(
    'datatables_page_length' => 25,
    'enable_cache' => false,
    'cache_duration' => 3600,
    'enable_debug' => false
));
?>

<form method="post" action="options.php">
    <?php
    settings_fields('wp_customer_settings');
    do_settings_sections('wp_customer_settings');
    ?>
    
    <table class="form-table">
        <!-- DataTables Settings -->
        <tr>
            <th scope="row">
                <label for="datatables_page_length">
                    <?php _e('DataTables Page Length', 'customer-management'); ?>
                </label>
            </th>
            <td>
                <select name="wp_customer_settings[datatables_page_length]" id="datatables_page_length">
                    <option value="10" <?php selected($options['datatables_page_length'], 10); ?>>10</option>
                    <option value="25" <?php selected($options['datatables_page_length'], 25); ?>>25</option>
                    <option value="50" <?php selected($options['datatables_page_length'], 50); ?>>50</option>
                    <option value="100" <?php selected($options['datatables_page_length'], 100); ?>>100</option>
                </select>
                <p class="description">
                    <?php _e('Number of rows displayed per page in tables', 'customer-management'); ?>
                </p>
            </td>
        </tr>

        <!-- Cache Settings -->
        <tr>
            <th scope="row"><?php _e('Cache Settings', 'customer-management'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wp_customer_settings[enable_cache]" 
                           value="1" <?php checked($options['enable_cache'], 1); ?>>
                    <?php _e('Enable caching', 'customer-management'); ?>
                </label>
                
                <div class="cache-options" style="margin-top: 10px;">
                    <label>
                        <?php _e('Cache Duration (seconds):', 'customer-management'); ?>
                        <input type="number" name="wp_customer_settings[cache_duration]" 
                               value="<?php echo esc_attr($options['cache_duration']); ?>" 
                               min="60" step="60">
                    </label>
                </div>
            </td>
        </tr>

        <!-- Debug Mode -->
        <tr>
            <th scope="row"><?php _e('Debug Mode', 'customer-management'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wp_customer_settings[enable_debug]" 
                           value="1" <?php checked($options['enable_debug'], 1); ?>>
                    <?php _e('Enable debug mode', 'customer-management'); ?>
                </label>
                <p class="description">
                    <?php _e('Shows additional debugging information in console', 'customer-management'); ?>
                </p>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>
