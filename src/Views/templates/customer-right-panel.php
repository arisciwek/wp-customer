


<div class="wp-customer-panel-header">
    <h2>Detail Customer: <span id="customer-header-name"></span></h2>
    <button type="button" class="wp-customer-close-panel">×</button>
</div>

<div class="wp-customer-panel-content">

<?php


// Debug untuk memastikan data ada
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== Customer Right Panel Debug ===');
    error_log('Template Data Available:');
    //error_log(print_r(get_defined_vars(), true));
}

// Make data available for all tabs
$panel_data = [
    'customer' => isset($customer) && is_object($customer) ? $customer : null,
    'access' => isset($access) ? $access : null,
    'controller' => isset($controller) ? $controller : null
];

// Di _branch_list.php
$branches = isset($panel_data['branches']) ? $panel_data['branches'] : [];
//$branch_model = isset($panel_data['branch_model']) ? $panel_data['branch_model'] : null;

// Di _employee_list.php 
$employees = isset($panel_data['employees']) ? $panel_data['employees'] : [];
//$employee_model = isset($panel_data['employee_model']) ? $panel_data['employee_model'] : null;

// Debug panel data
if (defined('WP_DEBUG') && WP_DEBUG) {
    //error_log('Panel Data Prepared:');
    //error_log(print_r($panel_data, true));
    //error_log('Panel Data [Customer]:' . print_r($panel_data['customer'], true));
}




?>

<div class="nav-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-customer-details nav-tab-active" data-tab="customer-details">Data Customer</a>
    <a href="#" class="nav-tab" data-tab="membership-info">Membership</a>
    <a href="#" class="nav-tab" data-tab="branch-list">Cabang</a>
    <a href="#" class="nav-tab" data-tab="employee-list">Staff</a>
</div>

<?php
// Pass data ke semua partial templates
$template_data = isset($panel_data) ? $panel_data : [];

foreach ([
    'customer/partials/_customer_details.php',
    'customer/partials/_customer_membership.php',
    'branch/partials/_branch_list.php',
    'employee/partials/_employee_list.php'
] as $template) {
    include_once WP_CUSTOMER_PATH . 'src/Views/templates/' . $template;
}
?>

</div>
