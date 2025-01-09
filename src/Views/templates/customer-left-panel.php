<?php
/**
 * File: customer-left-panel.php
 * Path: /wp-customer/src/Views/templates/customer-left-panel.php
 */
?>
<div id="wp-customer-left-panel" class="wp-customer-left-panel">
    <div class="wp-customer-panel-header">
        <h2>Daftar Customer</h2>
        <button type="button" class="button button-primary" id="add-customer-btn">
            Tambah Customer
        </button>
    </div>
    
    <div class="wp-customer-panel-content">
        <table id="customers-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nama Customer</th>
                    <th>Jumlah Kab/Kota</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>