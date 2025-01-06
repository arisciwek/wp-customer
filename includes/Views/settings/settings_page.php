<?php
/**
 * Customer Management Settings Page
 *
 * @package     CustomerManagement
 * @subpackage  Core/Views/Settings
 * @version     1.0.0
 * @author      Your Name
 * @copyright   2024 Your Organization
 * @license     GPL-2.0+
 * 
 * Description:
 * Main container for settings page. Handles tab display and routing
 * to appropriate settings sections. Provides interface for managing
 * plugin-wide configurations.
 * 
 * Path: includes/Views/settings/settings_page.php
 * Timestamp: 2024-01-06 11:00:00
 * 
 * Required Capabilities:
 * - manage_customer_settings
 * 
 * Dependencies:
 * - WordPress Settings API
 * - Customer Management Core Plugin
 * 
 * Changelog:
 * 1.0.0 - 2024-01-06
 * - Initial release
 * - Added general settings tab
 * - Implemented settings page container
 * - Added capability checks
 */


if (!defined('ABSPATH')) {
    die;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Define available tabs
$tabs = array(
    'general' => __('General Settings', 'customer-management')
);

// Security check
if (!current_user_can('manage_customer_settings')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'customer-management'));
}
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

    <div class="tab-content">
        <?php 
        // Load tab content
        $tab_file = WP_CUSTOMER_PATH . 'includes/Views/settings/tab-' . $current_tab . '.php';
        if (file_exists($tab_file)) {
            require_once $tab_file;
        }
        ?>
    </div>
</div>
