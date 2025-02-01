<?php
// Access template data directly
$customer = isset($panel_data['customer']) ? $panel_data['customer'] : null;
$access = isset($panel_data['access']) ? $panel_data['access'] : null;

// Debug log if needed
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== Customer Details Template Debug ===');
    //error_log('Customer Data: ' . print_r($customer, true));
    //error_log('Access Data: ' . print_r($access, true));
}
?>


<!-- in _customer_details.php -->
<div id="customer-details" class="tab-content">
    <?php if (isset($customer)): ?>
        <h3><?php echo esc_html($customer->name); ?></h3>
        <div id="customer-name"></div>
    <?php endif; ?>
    <div class="meta-info">
        <p><strong>--Jumlah Cabang:</strong> <span id="customer-branch-count"></span></p>
        <p><strong>--Dibuat:</strong> <span id="customer-created-at"></span></p>
        <p><strong>--Terakhir diupdate:</strong> <span id="customer-updated-at"></span></p>
    </div>
</div>

