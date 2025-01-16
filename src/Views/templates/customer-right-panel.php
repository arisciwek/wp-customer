


<div class="wp-customer-panel-header">
    <h2>Detail Customer: <span id="customer-header-name"></span></h2>
    <button type="button" class="wp-customer-close-panel">×</button>
</div>

<div class="wp-customer-panel-content">

<?php
if (isset($customer) && is_object($customer)) {
    // Debug untuk memastikan data ada
    error_log('=== Customer Data in Right Panel ===');
    error_log(print_r($customer, true));
    
    // Jadikan data available untuk semua tab
    $panel_data = [
        'customer' => $customer,
        'access' => isset($access) ? $access : null
    ];
} else {
    error_log('Customer data not available in right panel');
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
