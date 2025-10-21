<?php
/**
 * Membership Invoice Payment Modal Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/CompanyInvoice/Forms
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company-invoice/forms/membership-invoice-payment-modal.php
 *
 * Description: Template untuk modal pembayaran membership invoice.
 *              Digunakan untuk form pembayaran invoice membership.
 *              Includes payment method selection dan confirmation.
 *              Modal diisi data via JavaScript saat button "Bayar Sekarang" diklik.
 *
 * Usage:
 * - Included in company-invoice-dashboard.php
 * - Shown/hidden by JavaScript (company-invoice-payment-modal.js)
 * - Data populated dynamically via JavaScript
 *
 * Changelog:
 * 1.0.1 - 2025-10-18 (Task-2162)
 * - Added file upload field for payment proof
 * - Added file preview container
 * - Added file size indicator (max 5MB)
 * - Accepts JPG, PNG, PDF files
 *
 * 1.0.0 - 2025-01-17 (Review-07)
 * - Initial template version
 * - Extracted from JavaScript string to PHP template
 * - Added proper internationalization
 * - Improved maintainability
 */

defined('ABSPATH') || exit;
?>

<!-- Payment Modal -->
<div class="wp-customer-modal" id="invoice-payment-modal" style="display: none;">
    <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Pembayaran Invoice', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close dashicons dashicons-no-alt" aria-label="<?php esc_attr_e('Close', 'wp-customer'); ?>"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <p>
                <?php _e('Pembayaran untuk invoice', 'wp-customer'); ?>
                <strong id="payment-invoice-number">-</strong>
            </p>
            <p>
                <?php _e('Total:', 'wp-customer'); ?>
                <strong id="payment-invoice-amount">Rp 0</strong>
            </p>

            <div class="payment-details">
                <div class="form-row">
                    <label for="payment-method"><?php _e('Metode Pembayaran', 'wp-customer'); ?></label>
                    <select id="payment-method" name="payment_method">
                        <option value="transfer_bank"><?php _e('Transfer Bank', 'wp-customer'); ?></option>
                        <option value="virtual_account"><?php _e('Virtual Account', 'wp-customer'); ?></option>
                        <option value="kartu_kredit"><?php _e('Kartu Kredit', 'wp-customer'); ?></option>
                        <option value="e_wallet"><?php _e('E-Wallet', 'wp-customer'); ?></option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="proof-file">
                        <?php _e('Bukti Pembayaran', 'wp-customer'); ?>
                        <span class="optional-label"><?php _e('(Opsional)', 'wp-customer'); ?></span>
                    </label>
                    <input type="file"
                           id="proof-file"
                           name="proof_file"
                           accept="image/jpeg,image/png,application/pdf"
                           aria-describedby="file-help"/>
                    <p class="description" id="file-help">
                        <?php _e('Format: JPG, PNG, atau PDF. Maksimal 5MB.', 'wp-customer'); ?>
                    </p>
                    <div id="file-preview" class="file-preview" style="display: none;">
                        <!-- Preview akan diisi oleh JavaScript -->
                    </div>
                </div>
            </div>

            <div class="confirmation-notice">
                <p><?php _e('Dengan melanjutkan, Anda setuju untuk melakukan pembayaran invoice ini.', 'wp-customer'); ?></p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
            <button type="button" class="button modal-cancel"><?php _e('Batal', 'wp-customer'); ?></button>
            <button type="button"
                    class="button button-primary modal-confirm"
                    id="payment-confirm-btn"
                    data-invoice-id=""
                    data-invoice-number=""
                    data-amount="">
                <?php _e('Bayar Sekarang', 'wp-customer'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Cancel Invoice Modal -->
<div class="wp-customer-modal" id="invoice-cancel-modal" style="display: none;">
    <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="modal-title"><?php _e('Batalkan Invoice', 'wp-customer'); ?></h3>
            <button type="button" class="modal-close dashicons dashicons-no-alt" aria-label="<?php esc_attr_e('Close', 'wp-customer'); ?>"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <p>
                <?php _e('Apakah Anda yakin ingin membatalkan invoice', 'wp-customer'); ?>
                <strong id="cancel-invoice-number">-</strong>?
            </p>
            <div class="confirmation-notice" style="background: #fff3cd; border-left-color: #d63638;">
                <p style="color: #721c24;">
                    <strong><?php _e('Perhatian:', 'wp-customer'); ?></strong>
                    <?php _e('Invoice yang dibatalkan tidak dapat dikembalikan.', 'wp-customer'); ?>
                </p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
            <button type="button" class="button modal-cancel"><?php _e('Batal', 'wp-customer'); ?></button>
            <button type="button"
                    class="button button-primary modal-confirm"
                    id="cancel-confirm-btn"
                    data-invoice-id=""
                    style="background-color: #d63638; border-color: #d63638;">
                <?php _e('Ya, Batalkan', 'wp-customer'); ?>
            </button>
        </div>
    </div>
</div>
