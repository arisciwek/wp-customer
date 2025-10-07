
<?php
/**
 * Customer Right Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer-right-panel.php
 *
 * Description: Template untuk panel kanan detail customer
 *
 * Changelog:
 * 1.0.0 - 2024-12-20
 * - Initial creation
 * - Removed membership tab as membership applies at company level
 */

?>


<div class="wp-customer-panel-header">
    <h2>Detail Customer: <span id="customer-header-name"></span></h2>
    <button type="button" class="wp-customer-close-panel">×</button>
</div>

<div class="wp-customer-panel-content">


<div class="nav-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-customer-details nav-tab-active" data-tab="customer-details">Data Customer</a>
    <a href="#" class="nav-tab" data-tab="branch-list">Cabang</a>
    <a href="#" class="nav-tab" data-tab="employee-list">Staff</a>
</div>

<?php
// Pass data ke semua partial templates


foreach ([
    'customer/partials/_customer_details.php',
    'branch/partials/_customer_branch_list.php',
    'employee/partials/_employee_list.php'
] as $template) {
    include_once WP_CUSTOMER_PATH . 'src/Views/templates/' . $template;
}
?>

</div>
