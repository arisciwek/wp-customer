<?php
/**
 * Company Invoice Left Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
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

    <div class="wi-panel-content">
        <table id="company-invoices-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nomor Invoice</th>
                    <th>Customer</th>
                    <th>Cabang</th>
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
