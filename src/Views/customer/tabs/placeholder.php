<?php
/**
 * Customer Placeholder Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/tabs/placeholder.php
 *
 * Description: Placeholder tab untuk future expansion.
 *              Could be used for: Documents, Activity Log, Settings, etc.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following platform-staff pattern
 */

defined('ABSPATH') || exit;
?>

<div class="customer-placeholder-tab">
    <div class="customer-empty-state">
        <span class="dashicons dashicons-info-outline"></span>
        <p><?php echo esc_html__('Additional content will be available here', 'wp-customer'); ?></p>
    </div>
</div>
