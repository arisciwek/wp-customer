<?php
/**
 * Invoice Details Partial Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path : /wp-customer/src/Views/templates/company-invoice/partials/_company_invoice_details.php
 */
?>

<div id="invoice-details" class="tab-content active">
    <div class="invoice-details-section">
        <h3>Informasi Invoice</h3>
        <table class="form-table">
            <tr>
                <th>Nomor Invoice:</th>
                <td><span id="invoice-number">-</span></td>
            </tr>
            <tr>
                <th>Customer:</th>
                <td><span id="invoice-customer">-</span></td>
            </tr>
            <tr>
                <th>Cabang:</th>
                <td><span id="invoice-branch">-</span></td>
            </tr>
            <tr>
                <th>Jumlah:</th>
                <td><span id="invoice-amount">-</span></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span id="invoice-status" class="status-badge">-</span></td>
            </tr>
            <tr>
                <th>Jatuh Tempo:</th>
                <td><span id="invoice-due-date">-</span></td>
            </tr>
            <tr>
                <th>Tanggal Dibuat:</th>
                <td><span id="invoice-created-at">-</span></td>
            </tr>
            <tr>
                <th>Dibuat Oleh:</th>
                <td><span id="invoice-created-by">-</span></td>
            </tr>
        </table>
    </div>

    <div class="invoice-actions-section">
        <h3>Aksi</h3>
        <div id="invoice-actions-buttons">
            <!-- Buttons will be populated by JavaScript -->
        </div>
    </div>
</div>
