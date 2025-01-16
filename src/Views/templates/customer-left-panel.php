<?php
/**
 * File: customer-left-panel.php
 * Path: /wp-customer/src/Views/templates/customer-left-panel.php
 */
?>
<div id="wp-customer-left-panel" class="wp-customer-left-panel">
    <div class="wi-panel-header">
        <h2>Daftar Customer</h2>

        <?php if (current_user_can('add_customer')): ?>
            <button type="button" class="button button-primary" id="add-customer-btn">
                Tambah Customer
            </button>
        <?php endif; ?>


    </div>
    
    <div class="wi-panel-content">
        <table id="customers-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Nama Customer</th>
                    <th>Cabang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
