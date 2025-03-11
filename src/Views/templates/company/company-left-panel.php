<?php

/**
 * Company Left Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company/company-left-panel.php
 *
 * Description: Template untuk panel kiri yang menampilkan
 *              DataTable daftar perusahaan.
 */

defined('ABSPATH') || exit;
?>

<div id="wp-company-left-panel" class="wp-company-left-panel">
    <div class="wi-panel-header">
        <h2>Daftar Perusahaan</h2>
    </div>
    
    <div class="wi-panel-content">
        <table id="companies-table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Perusahaan</th>
                    <th>Tipe</th>
                    <th>Level</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
