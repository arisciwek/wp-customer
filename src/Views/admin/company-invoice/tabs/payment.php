<?php
/**
 * Company Invoice Payment Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/payment.php
 *
 * Description: Payment tab untuk company invoice detail panel.
 *              Uses lazy-load pattern.
 *              Shows payment history and upload payment proof.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Lazy-load pattern
 * - AJAX endpoint: load_company_invoice_payment_tab
 */

defined('ABSPATH') || exit;

// $data is passed from controller (invoice object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer') . '</p>';
    return;
}

$invoice = $data;
?>

<div class="wpdt-tab-autoload"
     data-ajax-action="load_company_invoice_payment_tab"
     data-invoice-id="<?php echo esc_attr($invoice->id); ?>">
    <div class="wpdt-tab-loading">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading payment information...', 'wp-customer'); ?></p>
    </div>
</div>
