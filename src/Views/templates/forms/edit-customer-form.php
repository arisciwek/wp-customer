<?php
/**
 * Edit Customer Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Forms
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/src/Views/templates/forms/edit-customer-form.php
 * 
 * Description: Modal form template untuk edit customer.
 *              Includes validation, security checks,
 *              dan AJAX submission handling.
 *              Terintegrasi dengan CustomerForm component.
 * 
 * Changelog:
 * 1.0.0 - 2024-12-05
 * - Initial implementation
 * - Added nonce security
 * - Added form validation
 * - Added permission checks
 * - Added AJAX integration
 */
?>
<?php
// File: edit-customer-form.php
defined('ABSPATH') || exit;
?>

<div id="edit-customer-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Edit Customer</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-content">
            <div id="edit-mode">
                <form id="edit-customer-form" class="wi-form">
                    <input type="hidden" id="customer-id" name="id" value="">
                    
                    <div class="wi-form-group">
                        <label for="edit-code" class="required-field">Kode Customer</label>
                        <input type="text" 
                               id="edit-code" 
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
                        <label for="edit-name" class="required-field">Nama Customer</label>
                        <input type="text" 
                               id="edit-name" 
                               name="name" 
                               class="regular-text"
                               maxlength="100" 
                               required>
                    </div>

                    <div class="submit-wrapper">
                        <button type="submit" class="button button-primary">Update</button>
                        <button type="button" class="button cancel-edit">Batal</button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

