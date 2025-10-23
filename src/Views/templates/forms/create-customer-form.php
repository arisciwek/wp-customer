<?php
/**
 * Create Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/forms/create-customer-form.php
 *
 * Description: Template form untuk menambah customer baru.
 *              Menggunakan modal dialog untuk tampilan form.
 *              Includes validasi client-side dan permission check.
 *              Terintegrasi dengan AJAX submission dan toast notifications.
 *              Menggunakan shared component untuk consistency.
 *
 * Dependencies:
 * - WordPress admin styles
 * - customer-toast.js for notifications
 * - customer-form.css for styling
 * - customer-form.js for handling
 * - partials/customer-form-fields.php (shared component)
 *
 * Changelog:
 * 1.1.0 - 2025-01-21 (Task-2165 Form Sync)
 * - Refactored to use shared component customer-form-fields.php
 * - Ensures field consistency with self-register form
 * - Single source of truth for form structure
 * - Removed duplicate field definitions
 *
 * 1.0.0 - 2024-12-02 18:30:00
 * - Initial release
 * - Added permission check
 * - Added nonce security
 * - Added form validation
 * - Added AJAX integration
 */

defined('ABSPATH') || exit;

?>

<div id="create-customer-modal" class="modal-overlay" style="display: none;">
   <div class="modal-container">
       <form id="create-customer-form" method="post">
           <div class="modal-header">
               <h3><?php _e('Tambah Customer', 'wp-customer'); ?></h3>
               <button type="button" class="modal-close" aria-label="Close">&times;</button>
           </div>

           <div class="modal-content">
               <?php wp_nonce_field('wp_customer_nonce'); ?>
               <input type="hidden" name="action" value="create_customer">

               <?php
               // Set args for shared component
               $args = [
                   'mode' => 'admin-create',
                   'layout' => 'single-column',
                   'field_classes' => 'regular-text',
                   'wrapper_classes' => 'wp-customer-form-group'
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
           </div>

           <div class="modal-footer">
               <button type="submit" class="button button-primary">
                   <?php _e('Simpan', 'wp-customer'); ?>
               </button>
               <button type="button" class="button cancel-create">
                   <?php _e('Batal', 'wp-customer'); ?>
               </button>
               <span class="spinner"></span>
           </div>
       </form>
   </div>
</div>
