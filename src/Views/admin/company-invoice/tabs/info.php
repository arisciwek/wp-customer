<?php
/**
 * Company Invoice Info Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/info.php
 *
 * Description: Info tab untuk company invoice detail panel.
 *              Uses lazy-load pattern dengan wpdt-tab-autoload class.
 *              Content dimuat via AJAX saat tab pertama kali diklik.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Lazy-load pattern
 * - AJAX endpoint: load_company_invoice_info_tab
 */

defined('ABSPATH') || exit;

// $data is passed from controller (invoice object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer') . '</p>';
    return;
}

$invoice = $data;
?>

<?php
// Direct include content (no lazy-load for first tab)
include WP_CUSTOMER_PATH . 'src/Views/admin/company-invoice/tabs/partials/info-content.php';
?>
