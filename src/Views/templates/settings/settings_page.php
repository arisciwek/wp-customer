<?php
/**
 * Settings Page Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/settings_page.php
 *
 * Description: Main settings page template that includes tab navigation
 *              Handles tab switching and settings error notices
 *
 * Changelog:
 * 1.0.1 - 2024-12-08
 * - Added WIModal template integration
 * - Enhanced template structure for modals
 * - Improved documentation
 *
 * Changelog:
 * v1.0.0 - 2024-11-25
 * - Initial version
 * - Add main settings page layout
 * - Add tab navigation
 * - Add settings error notices support
 * - Add tab content rendering
 */

if (!defined('ABSPATH')) {
    die;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

$tabs = array(
    'general' => __('Pengaturan Umum', 'wp-customer'),
    'permissions' => __('Hak Akses', 'wp-customer'),
    'membership' => __('Membership Levels', 'wp-customer')

);

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_key => $tab_caption): ?>
            <?php $active = $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>
            <a href="<?php echo add_query_arg('tab', $tab_key); ?>" 
               class="nav-tab <?php echo $active; ?>">
                <?php echo esc_html($tab_caption); ?>
            </a>
        <?php endforeach; ?>
    </nav>

</div>
