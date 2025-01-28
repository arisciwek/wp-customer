<?php
defined('ABSPATH') || exit;

// Debug log untuk data yang diterima
if (defined('WP_DEBUG') && WP_DEBUG) {
    //error_log('Template data received:');
    //error_log(print_r($customer, true)); 
}
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
            <?php 
            // Pass variables ke setiap template
            $panel_data = compact('customer', 'access', 'branches', 'employees');
            
            require WP_CUSTOMER_PATH . 'src/Views/templates/customer/partials/_customer_details.php';
            require WP_CUSTOMER_PATH . 'src/Views/templates/customer/partials/_customer_membership.php';
            require WP_CUSTOMER_PATH . 'src/Views/templates/branch/partials/_branch_list.php';
            require WP_CUSTOMER_PATH . 'src/Views/templates/employee/partials/_employee_list.php';
            ?>
        </div>
    </div>

    <?php

    // Include related modals
    require WP_CUSTOMER_PATH . 'src/Views/templates/employee/forms/create-employee-form.php';
    require WP_CUSTOMER_PATH . 'src/Views/templates/employee/forms/edit-employee-form.php';

    // Include related modals
    require WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/create-branch-form.php';
    require WP_CUSTOMER_PATH . 'src/Views/templates/branch/forms/edit-branch-form.php';

    ?>
    
</div>
