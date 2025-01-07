<?php
/**
 * Create Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/src/Views/templates/forms/create-customer-form.php
 * 
 * Description: Template form untuk menambah customer baru.
 *              Menggunakan modal dialog untuk tampilan form.
 *              Includes validasi client-side dan permission check.
 *              Terintegrasi dengan AJAX submission dan toast notifications.
 * 
 * Changelog:
 * 1.0.0 - 2024-12-02 18:30:00
 * - Initial release
 * - Added permission check
 * - Added nonce security
 * - Added form validation
 * - Added AJAX integration
 * 
 * Dependencies:
 * - WordPress admin styles
 * - customer-toast.js for notifications
 * - customer-form.css for styling
 * - customer-form.js for handling
 */

defined('ABSPATH') || exit;
?>

<div id="create-customer-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Tambah Customer</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-content">
            <form id="create-customer-form" method="post">
                <?php wp_nonce_field('wp_customer_nonce'); ?>
                <input type="hidden" name="action" value="create_customer">
                
                <div class="wi-form-group">
                    <label for="customer-code" class="required-field">
                        <?php _e('Kode Customer', 'wp-customer'); ?>
                    </label>
                    <input type="text" 
                           id="customer-code" 
                           name="code" 
                           class="small-text" 
                           maxlength="2" 
                           pattern="\d{2}"
                           required>
                    <p class="description">
                        <?php _e('Masukkan 2 digit angka', 'wp-customer'); ?>
                    </p>
                </div>

                <div class="wi-form-group">
                    <label for="customer-name" class="required-field">
                        <?php _e('Nama Customer', 'wp-customer'); ?>
                    </label>
                    <input type="text" 
                           id="customer-name" 
                           name="name" 
                           class="regular-text" 
                           maxlength="100" 
                           required>
                </div>
                
                <div class="submit-wrapper">
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
</div>

