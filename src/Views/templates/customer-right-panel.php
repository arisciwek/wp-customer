<?php

defined('ABSPATH') || exit;

// At top of file
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Template data received:');
    error_log(print_r($customer, true)); 
}

/*
// Debug log untuk $access
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== Debug Customer Right Panel ===');
    error_log('$access variable: ' . print_r(isset($access) ? $access : 'undefined', true)); 
    if (isset($access)) {
        error_log('access_type: ' . (isset($access['access_type']) ? $access['access_type'] : 'not set'));
        error_log('has_access: ' . (isset($access['has_access']) ? $access['has_access'] : 'not set'));
    }
    error_log('Current user ID: ' . get_current_user_id());
    error_log('Current user roles: ' . print_r(wp_get_current_user()->roles, true));
    error_log('=== End Debug ===');
}
*/
?>

<div class="wp-customer-preview"> 
    <div class="wp-customer-panel-header">
        <h2>Detail Customer</h2>
        <?php if (isset($customer) && is_object($customer)): ?>
            <h4><?php echo esc_html($customer->name); ?></h4>
        <?php endif; ?>
        <button type="button" class="wp-customer-close-panel">×</button>
    </div>  

    <div class="wp-customer-panel-content">
        <div class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" data-tab="customer-details">Data Customer</a>
            <a href="#" class="nav-tab" data-tab="membership-info">Membership</a>
            <a href="#" class="nav-tab" data-tab="branch-list">Cabang</a>
            <a href="#" class="nav-tab" data-tab="employee-list">Staff</a>
        </div>

        <div class="tab-content-wrapper">
            <div id="customer-details" class="tab-content tab-content-active">
                <?php include WP_CUSTOMER_PATH . 'src/Views/templates/customer/partials/_customer_details.php'; ?>
            </div>
            <div id="membership-info" class="tab-content">
                <?php include WP_CUSTOMER_PATH . 'src/Views/templates/customer/partials/_customer_membership.php'; ?>
            </div>
            <div id="branch-list" class="tab-content">
                <?php include WP_CUSTOMER_PATH . 'src/Views/templates/branch/partials/_branch_list.php'; ?>
            </div>
            <div id="employee-list" class="tab-content">
                <?php include WP_CUSTOMER_PATH . 'src/Views/templates/employee/partials/_employee_list.php'; ?>
            </div>
        </div>
    </div>
</div>
