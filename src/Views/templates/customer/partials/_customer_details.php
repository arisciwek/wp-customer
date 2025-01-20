<?php
// Access template data directly
$customer = isset($panel_data['customer']) ? $panel_data['customer'] : null;
$access = isset($panel_data['access']) ? $panel_data['access'] : null;

// Debug dengan lebih detail
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== Customer Details Template Debug ===');
    error_log('$panel_data: ' . print_r($panel_data, true));
    error_log('$customer: ' . print_r($customer, true));
    error_log('$access: ' . print_r($access, true));
}

?>

<div id="customer-details" class="tab-content active">
    <?php if (isset($customer) && is_object($customer)): ?>
        <h3><?php echo esc_html($customer->name); ?></h3>
        <div class="meta-info">
            <p><strong>-- Jumlah Cabang:</strong> <?php echo esc_html($customer->branch_count); ?></p>
            <p><strong>-- Dibuat:</strong> <?php echo date('j/n/Y, H:i.s', strtotime($customer->created_at)); ?></p>
            <p><strong>-- Terakhir diupdate:</strong> <?php echo date('j/n/Y, H:i.s', strtotime($customer->updated_at)); ?></p>
        </div>
    <?php endif; ?>
</div>



<div id="customer-details" class="tab-content-active">
    <?php if (isset($customer)): ?>
        <h3><?php echo esc_html($customer->name); ?></h3>
        <div id="customer-name"></div>
    <?php endif; ?>
        <div class="meta-info">
            <p><strong>Jumlah Cabang:</strong> <span id="customer-branch-count"></span></p>
            <p><strong>Dibuat:</strong> <span id="customer-created-at"></span></p>
            <p><strong>Terakhir diupdate:</strong> <span id="customer-updated-at"></span></p>
        </div>

</div>
