<?php
/**
 * Membership Invoice Payment Proof Modal Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company-invoice/partials/membership-invoice-payment-proof-modal.php
 *
 * Description: Modal untuk menampilkan bukti pembayaran membership invoice.
 *              Includes image preview dan tombol download.
 *              Compatible dengan berbagai format file (jpg, png, pdf).
 *
 * Changelog:
 * 1.0.1 - 2025-10-18 (Task-2162 Review-03)
 * - Added Perusahaan field (Branch Name)
 * - Positioned above Nomor Invoice
 *
 * 1.0.0 - 2025-10-18
 * - Initial creation
 * - Added payment proof display
 * - Added download button placeholder
 */

defined('ABSPATH') || exit;
?>

<!-- Payment Proof Modal -->
<div id="payment-proof-modal" class="wp-customer-modal" style="display: none;">
    <div class="wp-customer-modal-overlay"></div>
    <div class="wp-customer-modal-content payment-proof-modal-content">
        <div class="wp-customer-modal-header">
            <h2>Bukti Pembayaran Invoice</h2>
            <button type="button" class="wp-customer-modal-close" id="close-payment-proof-modal">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>

        <div class="wp-customer-modal-body">
            <!-- Payment Information -->
            <div class="payment-proof-info">
                <table class="form-table">
                    <tr>
                        <th>Perusahaan:</th>
                        <td><strong id="proof-branch-name">-</strong></td>
                    </tr>
                    <tr>
                        <th>Nomor Invoice:</th>
                        <td><strong id="proof-invoice-number">-</strong></td>
                    </tr>
                    <tr>
                        <th>Tanggal Pembayaran:</th>
                        <td id="proof-payment-date">-</td>
                    </tr>
                    <tr>
                        <th>Jumlah Dibayar:</th>
                        <td><strong id="proof-payment-amount">-</strong></td>
                    </tr>
                    <tr>
                        <th>Metode Pembayaran:</th>
                        <td id="proof-payment-method">-</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span id="proof-payment-status" class="status-badge">-</span></td>
                    </tr>
                    <tr>
                        <th>Catatan:</th>
                        <td id="proof-payment-notes">-</td>
                    </tr>
                </table>
            </div>

            <!-- Payment Proof Preview -->
            <div class="payment-proof-preview">
                <h3>Bukti Pembayaran</h3>
                <div id="proof-file-preview" class="proof-preview-container">
                    <!-- Preview akan dimuat di sini via JavaScript -->
                    <div class="proof-loading">
                        <span class="dashicons dashicons-update spin"></span>
                        <p>Memuat bukti pembayaran...</p>
                    </div>
                </div>
            </div>

            <!-- Download Button -->
            <div class="payment-proof-actions">
                <button type="button" class="button button-primary" id="download-payment-proof" disabled>
                    <span class="dashicons dashicons-download"></span>
                    Download Bukti Pembayaran
                </button>
                <p class="description" style="margin-top: 10px; font-style: italic; color: #666;">
                    * Fitur download akan segera tersedia
                </p>
            </div>
        </div>

        <div class="wp-customer-modal-footer">
            <button type="button" class="button" id="cancel-payment-proof-modal">Tutup</button>
        </div>
    </div>
</div>
