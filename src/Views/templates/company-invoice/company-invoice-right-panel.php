<?php
/**
 * Company Invoice Right Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company-invoice/company-invoice-right-panel.php
 *
 * Description: Template untuk panel kanan detail company invoice
 *
 * Changelog:
 * 1.0.0 - 2024-12-25
 * - Initial creation
 * - Added invoice details and payment tabs
 */

?>


<div class="wp-company-invoice-panel-header">
    <h2>Detail Invoice: <span id="invoice-header-number"></span></h2>
    <button type="button" class="wp-company-invoice-close-panel">×</button>
</div>

<div class="wp-company-invoice-panel-content">
    <div class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-invoice-details nav-tab-active" data-tab="invoice-details">Detail Invoice</a>
        <a href="#" class="nav-tab" data-tab="payment-info">Info Pembayaran</a>
    </div>

    <?php
    // Include partial templates
    include_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/partials/_company_invoice_details.php';
    include_once WP_CUSTOMER_PATH . 'src/Views/templates/company-invoice/partials/_company_invoice_payment_info.php';
    ?>
</div>
