<?php
/**
 * Company Invoice Left Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company-invoice/company-invoice-left-panel.php
 */
?>
<div id="wp-company-invoice-left-panel" class="wp-company-invoice-left-panel">
    <div class="wi-panel-header">
        <h2>Daftar Invoice Perusahaan</h2>

        <div id="tombol-tambah-invoice"></div>
    </div>

    <!-- Filter Status Pembayaran -->
    <div class="wi-panel-filters" style="padding: 10px 15px; background: #f5f5f5; border-bottom: 1px solid #ddd;">
        <strong>Filter Status:</strong>
        <label style="margin-left: 10px;">
            <input type="checkbox" id="filter-pending" checked> Belum Dibayar
        </label>
        <label style="margin-left: 10px;">
            <input type="checkbox" id="filter-pending-payment"> Menunggu Validasi
        </label>
        <label style="margin-left: 10px;">
            <input type="checkbox" id="filter-paid"> Lunas
        </label>
        <label style="margin-left: 10px;">
            <input type="checkbox" id="filter-cancelled"> Dibatalkan
        </label>
    </div>

    <div class="wi-panel-content">
        <table id="company-invoices-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nomor Invoice</th>
                    <th>Cabang</th>
                    <th>Level</th>
                    <th>Period</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Jatuh Tempo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
