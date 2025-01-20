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

<!-- Verification Display for PHP vs JS Comparison -->
<div id="customer-details" class="tab-content-active">
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

<style>
.customer-header {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.customer-code {
    font-size: 0.9em;
    color: #666;
    padding: 4px 8px;
    background: #f0f0f1;
    border-radius: 4px;
}

.customer-main-info,
.customer-stats,
.customer-meta {
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
}

.info-group,
.stat-item,
.meta-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    padding: 5px 0;
}

.info-group label,
.stat-item label,
.meta-item label {
    min-width: 150px;
    font-weight: 600;
    color: #1d2327;
}

.stat-value {
    font-size: 1.2em;
    font-weight: bold;
    color: #2271b1;
}
</style>
