<?php
/**
 * Customer Registration Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Auth
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/auth/register.php
 *
 * Description: Template untuk form registrasi customer baru.
 *              Menangani pendaftaran user WordPress sekaligus data customer.
 *              Form mencakup field username, email, password dan data customer
 *              seperti nama perusahaan, NIB, dan NPWP.
 *              Menggunakan shared component untuk consistency.
 *
 * Dependencies:
 * - jQuery
 * - wp-customer-toast
 * - WordPress AJAX
 * - partials/customer-form-fields.php (shared component)
 *
 * Changelog:
 * 1.1.0 - 2025-01-21 (Task-2165 Form Sync)
 * - Refactored to use shared component customer-form-fields.php
 * - Ensures field consistency with admin-create form
 * - Single source of truth for form structure
 *
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added registration form with validation
 * - Added AJAX submission handling
 * - Added NPWP formatter
 */

defined('ABSPATH') || exit;
?>

<h2><?php _e('Daftar Customer Baru', 'wp-customer'); ?></h2>

<form id="customer-register-form" class="wp-customer-form" method="post">
    <?php wp_nonce_field('wp_customer_register', 'register_nonce'); ?>

    <?php
    // Set args for shared component
    $args = [
        'mode' => 'self-register',
        'layout' => 'single-column',
        'field_classes' => 'regular-text',
        'wrapper_classes' => 'form-group'
    ];

    // Try multiple path resolution methods
    $template_path = null;

    // Method 1: Using WP_CUSTOMER_PATH constant (if available)
    if (defined('WP_CUSTOMER_PATH')) {
        $template_path = WP_CUSTOMER_PATH . 'src/Views/templates/partials/customer-form-fields.php';
    }

    // Method 2: Fallback to __FILE__ relative path
    if (!$template_path || !file_exists($template_path)) {
        $template_path = dirname(dirname(__FILE__)) . '/partials/customer-form-fields.php';
    }

    // Method 3: Last resort - hardcoded absolute path
    if (!file_exists($template_path)) {
        $template_path = '/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-customer/src/Views/templates/partials/customer-form-fields.php';
    }

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p class="error">Template component not found after trying all methods!</p>';
        echo '<p class="error">WP_CUSTOMER_PATH defined: ' . (defined('WP_CUSTOMER_PATH') ? 'YES - ' . WP_CUSTOMER_PATH : 'NO') . '</p>';
        echo '<p class="error">Final path tried: ' . esc_html($template_path) . '</p>';
        echo '<p class="error">File readable: ' . (is_readable($template_path) ? 'YES' : 'NO') . '</p>';
    }
    ?>

    <div class="wp-customer-submit clearfix">
        <div class="form-submit">
            <button type="submit" class="button button-primary">
                <?php _e('Daftar', 'wp-customer'); ?>
            </button>
        </div>
    </div>
</form>
