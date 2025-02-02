<?php


defined('ABSPATH') || exit;
?>


<!-- in _customer_details.php -->
<div id="customer-details" class="tab-content">
    <div id="customer-name"></div>
    <div class="export-actions">
        <button type="button" class="button wp-mpdf-customer-detail-export-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Generate PDF', 'wp-customer'); ?>
        </button>
        <button type="button" class="button  wp-docgen-customer-detail-expot-document">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Export DOCX', 'wp-customer'); ?>
        </button>
        <button type="button" class="button wp-docgen-customer-detail-expot-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Export PDF', 'wp-customer'); ?>
        </button>
</div>    
    <div class="meta-info">
        <p><strong>--Jumlah Cabang:</strong> <span id="customer-branch-count"></span></p>
        <p><strong>--Dibuat:</strong> <span id="customer-created-at"></span></p>
        <p><strong>--Terakhir diupdate:</strong> <span id="customer-updated-at"></span></p>
    </div>
</div>

