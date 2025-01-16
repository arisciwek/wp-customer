<?php
// Get customer data if not already available
if (!isset($customer) && isset($_POST['id'])) {
    $controller = new \WPCustomer\Controllers\CustomerController();
    $data = $controller->show();
    if ($data) {
        $customer = $data['customer'];
        $branch_count = $data['branch_count'];
        $access_type = $data['access_type'];
    }
}
?>

<div id="customer-details" class="tab-content active">
    <?php if (isset($customer)): ?>
        <h3><?php echo esc_html($customer->name); ?></h3>
        <div class="meta-info">
            <p><strong>Jumlah Cabang:</strong> <?php echo esc_html($branch_count); ?></p>
            <p><strong>Dibuat:</strong> <?php echo esc_html($customer->created_at); ?></p>
            <p><strong>Terakhir diupdate:</strong> <?php echo esc_html($customer->updated_at); ?></p>
            <?php if (WP_DEBUG): ?>
                <pre><?php print_r($customer); ?></pre>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div id="customer-name"></div>
        <div class="meta-info">
            <p><strong>Jumlah Cabang:</strong> <span id="customer-branch-count"></span></p>
            <p><strong>Dibuat:</strong> <span id="customer-created-at"></span></p>
            <p><strong>Terakhir diupdate:</strong> <span id="customer-updated-at"></span></p>
        </div>
    <?php endif; ?>
</div>
