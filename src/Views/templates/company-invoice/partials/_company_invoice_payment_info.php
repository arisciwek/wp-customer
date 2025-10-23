<?php
/**
 * Invoice Payment Info Partial Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path /wp-customer/src/Views/templates/company-invoice/partials/_company_invoice_payment_info.php
 */
?>

<div id="payment-info" class="tab-content">
    <div class="payment-info-section">
        <h3>Informasi Pembayaran</h3>
        <div id="payment-details">
            <!-- Payment details will be loaded here -->
        </div>

        <div id="payment-history">
            <h4>Riwayat Pembayaran</h4>
            <table id="payment-history-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
