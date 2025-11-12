<?php
/**
 * Settings Page Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/settings_page.php
 *
 * Description: Main settings page template with tab navigation
 *              Following wp-app-core standardized pattern (TODO-2198)
 *              GLOBAL SCOPE: Save & Reset buttons at page level
 *
 * Changelog:
 * 2.0.0 - 2025-01-13 (TODO-2198)
 * - BREAKING: Complete refactor to match wp-app-core pattern
 * - Added page-level Save & Reset buttons (sticky footer)
 * - Added tab config for button labels and form IDs
 * - Added custom notifications per tab (save/reset)
 * - Added controller integration for tab rendering
 * - Added wpwpc_settings_footer_content hook
 * 1.0.1 - 2024-12-08
 * - Added WIModal template integration
 * - Enhanced template structure for modals
 * - Improved documentation
 * 1.0.0 - 2024-11-25
 * - Initial version
 */

if (!defined('ABSPATH')) {
    die;
}

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

$tabs = [
    'general' => __('Pengaturan Umum', 'wp-customer'),
    'invoice-payment' => __('Invoice & Payment', 'wp-customer'),
    'permissions' => __('Hak Akses', 'wp-customer'),
    'membership-levels' => __('Membership Levels', 'wp-customer'),
    'membership-features' => __('Membership Features', 'wp-customer'),
    'demo-data' => __('Demo Data', 'wp-customer')
];

// Tab configuration for buttons
$tab_config = [
    'general' => [
        'save_label' => __('Simpan Pengaturan Umum', 'wp-customer'),
        'reset_action' => 'reset_general',
        'reset_title' => __('Reset Pengaturan Umum?', 'wp-customer'),
        'reset_message' => __('Apakah Anda yakin ingin mereset semua pengaturan umum ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.', 'wp-customer'),
        'form_id' => 'wp-customer-general-settings-form'
    ],
    'invoice-payment' => [
        'save_label' => __('Simpan Pengaturan Invoice', 'wp-customer'),
        'reset_action' => 'reset_invoice_payment',
        'reset_title' => __('Reset Pengaturan Invoice?', 'wp-customer'),
        'reset_message' => __('Apakah Anda yakin ingin mereset semua pengaturan invoice ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.', 'wp-customer'),
        'form_id' => 'wp-customer-invoice-payment-settings-form'
    ],
    'permissions' => [
        'save_label' => __('Simpan Hak Akses', 'wp-customer'),
        'reset_action' => 'reset_permissions',
        'reset_title' => __('Reset Hak Akses?', 'wp-customer'),
        'reset_message' => __('Apakah Anda yakin ingin mereset semua hak akses ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.', 'wp-customer'),
        'form_id' => 'wp-customer-permissions-form'
    ],
    'membership-levels' => [
        'save_label' => __('Simpan Membership Levels', 'wp-customer'),
        'reset_action' => 'reset_membership_levels',
        'reset_title' => __('Reset Membership Levels?', 'wp-customer'),
        'reset_message' => __('Apakah Anda yakin ingin mereset semua membership levels ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.', 'wp-customer'),
        'form_id' => 'wp-customer-membership-levels-form'
    ],
    'membership-features' => [
        'save_label' => __('Simpan Membership Features', 'wp-customer'),
        'reset_action' => 'reset_membership_features',
        'reset_title' => __('Reset Membership Features?', 'wp-customer'),
        'reset_message' => __('Apakah Anda yakin ingin mereset semua membership features ke nilai default?\n\nTindakan ini tidak dapat dibatalkan.', 'wp-customer'),
        'form_id' => 'wp-customer-membership-features-form'
    ],
    'demo-data' => [
        'save_label' => __('Simpan Pengaturan Demo', 'wp-customer'),
        'reset_action' => '', // No reset for demo-data tab
        'reset_title' => '',
        'reset_message' => '',
        'form_id' => 'wp-customer-demo-data-form'
    ],
];

$current_config = $tab_config[$current_tab] ?? $tab_config['general'];

?>

<div class="wrap wp-customer-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    // GLOBAL SCOPE: Page-level notification handling
    // Suppress WordPress default notices when we have custom tab-specific notices
    $show_custom_notice = false;

    // Check if we have custom save notice
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true' && isset($_GET['saved_tab'])) {
        $saved_tab = sanitize_key($_GET['saved_tab']);
        if ($saved_tab === $current_tab) {
            $show_custom_notice = true;
        }
    }

    // Check if we have custom reset notice
    if (isset($_GET['reset']) && isset($_GET['reset_tab'])) {
        $reset_tab = sanitize_key($_GET['reset_tab']);
        if ($reset_tab === $current_tab) {
            $show_custom_notice = true;
        }
    }

    // Only show WordPress default notices if we don't have custom notices
    if (!$show_custom_notice) {
        settings_errors();
    }
    ?>

    <?php
    // ABSTRACT PATTERN: Get notification messages from controller via hook
    // Each tab controller registers their messages via wpc_settings_notification_messages hook
    $notification_messages = isset($controller) ? $controller->getNotificationMessages() : ['save_messages' => [], 'reset_messages' => []];
    $save_messages = $notification_messages['save_messages'];
    $reset_messages = $notification_messages['reset_messages'];

    // Show save success notices
    // Only show if saved_tab matches current_tab (prevent showing on tab switch)
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true' && isset($_GET['saved_tab'])) {
        $saved_tab = sanitize_key($_GET['saved_tab']);

        // Only show notice if we're on the same tab that was saved
        if ($saved_tab === $current_tab) {
            // Get message from controller-registered messages
            $success_message = $save_messages[$current_tab] ?? __('Pengaturan berhasil disimpan.', 'wp-customer');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php echo esc_html($success_message); ?></strong></p>
            </div>
            <?php
        }
    }

    // Show reset success/error notices
    // Only show if reset_tab matches current_tab (prevent showing on tab switch)
    if (isset($_GET['reset']) && isset($_GET['reset_tab'])) {
        $reset_status = sanitize_key($_GET['reset']);
        $reset_tab = sanitize_key($_GET['reset_tab']);

        // Only show notice if we're on the same tab that was reset
        if ($reset_tab === $current_tab) {
            if ($reset_status === 'success') {
                // Get message from controller-registered messages
                $success_message = $reset_messages[$current_tab] ?? __('Pengaturan berhasil direset ke nilai default.', 'wp-customer');
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html($success_message); ?></strong></p>
                </div>
                <?php
            } elseif ($reset_status === 'error') {
                $error_message = isset($_GET['message'])
                    ? sanitize_text_field($_GET['message'])
                    : __('Gagal mereset pengaturan.', 'wp-customer');
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php _e('Error:', 'wp-customer'); ?></strong> <?php echo esc_html($error_message); ?></p>
                </div>
                <?php
            }
        }
    }
    ?>

    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <?php foreach ($tabs as $tab_key => $tab_caption): ?>
            <?php
            $active = $current_tab === $tab_key ? 'nav-tab-active' : '';
            // Clean URL: remove reset/settings-updated parameters when switching tabs
            $tab_url = remove_query_arg(['reset', 'reset_tab', 'settings-updated', 'saved_tab', 'message']);
            $tab_url = add_query_arg('tab', $tab_key, $tab_url);
            ?>
            <a href="<?php echo esc_url($tab_url); ?>"
               class="nav-tab <?php echo $active; ?>"
               data-tab="<?php echo esc_attr($tab_key); ?>">
                <?php echo esc_html($tab_caption); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="tab-content">
        <?php
        // Load the tab view through controller
        if (isset($controller)) {
            $controller->loadTabView($current_tab);
        }
        ?>
    </div>

    <?php
    /**
     * Hook: wpc_settings_footer_content
     * Allows tabs to customize footer content (e.g., info message for AJAX tabs)
     *
     * @param string $footer_html  Default footer HTML (buttons)
     * @param string $current_tab  Current tab slug
     * @param array  $current_config Current tab configuration
     *
     * @return string Custom footer HTML or empty string to hide footer
     */

    // Build default footer HTML
    ob_start();
    ?>
    <p class="submit" style="margin: 0;">
        <button type="submit"
                id="wpapp-settings-save"
                class="button button-primary"
                data-current-tab="<?php echo esc_attr($current_tab); ?>"
                data-form-id="<?php echo esc_attr($current_config['form_id']); ?>">
            <?php echo esc_html($current_config['save_label']); ?>
        </button>

        <?php if (!empty($current_config['reset_action'])): ?>
        <button type="button"
                id="wpapp-settings-reset"
                class="button button-secondary"
                data-current-tab="<?php echo esc_attr($current_tab); ?>"
                data-form-id="<?php echo esc_attr($current_config['form_id']); ?>"
                data-reset-title="<?php echo esc_attr($current_config['reset_title']); ?>"
                data-reset-message="<?php echo esc_attr($current_config['reset_message']); ?>">
            <?php _e('Reset ke Default', 'wp-customer'); ?>
        </button>
        <?php endif; ?>
    </p>
    <?php
    $default_footer_html = ob_get_clean();

    // Allow tabs to customize footer
    $footer_content = apply_filters('wpc_settings_footer_content', $default_footer_html, $current_tab, $current_config);

    // Render footer if content exists
    if (!empty($footer_content)):
    ?>
    <!-- GLOBAL SCOPE: Page-level footer for ALL tabs -->
    <div class="settings-page-footer" style="position: sticky; bottom: 0; background: #f0f0f1; padding: 15px 20px; border-top: 1px solid #c3c4c7; margin: 20px -20px -10px -20px; z-index: 100;">
        <?php echo $footer_content; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Confirmation Templates -->
<?php
    require_once WP_CUSTOMER_PATH . 'src/Views/components/confirmation-modal.php';
    if (function_exists('wp_customer_render_confirmation_modal')) {
        wp_customer_render_confirmation_modal();
    }
?>
