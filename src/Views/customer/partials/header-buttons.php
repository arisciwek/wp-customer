<?php
/**
 * Customer Dashboard - Header Buttons
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/partials/header-buttons.php
 *
 * Description: Action buttons untuk customer dashboard header.
 *              Includes Add New Customer button.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following platform-staff pattern
 */

defined('ABSPATH') || exit;
?>

<?php if (current_user_can('add_customer')): ?>
    <a href="#" class="button button-primary customer-add-btn" id="add-customer-btn">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php echo esc_html__('Add New Customer', 'wp-customer'); ?>
    </a>
<?php endif; ?>
