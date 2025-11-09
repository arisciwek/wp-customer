<?php
/**
 * Company Invoice Company Content (Partial)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/CompanyInvoice/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company-invoice/tabs/partials/company-content.php
 *
 * Description: Lazy-loaded content untuk company tab.
 *              Shows company/branch information related to invoice.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2196)
 * - Initial implementation
 * - Company/branch details display
 */

defined('ABSPATH') || exit;

// $invoice and $company should be available from parent template
if (!isset($invoice) && isset($data)) {
    $invoice = $data;
}

if (!isset($invoice) || !is_object($invoice)) {
    echo '<p>' . esc_html__('Invoice data not available', 'wp-customer'); ?></p>';
    return;
}

if (!isset($company) || !is_object($company)) {
    echo '<p>' . esc_html__('Company data not available', 'wp-customer'); ?></p>';
    return;
}
?>

<div class="company-details-section">
    <h3><?php esc_html_e('Company Information', 'wp-customer'); ?></h3>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Company Code:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->code ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Company Name:', 'wp-customer'); ?></th>
            <td><strong><?php echo esc_html($company->name ?? '-'); ?></strong></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Type:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->type === 'pusat' ? 'Pusat' : 'Cabang'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Email:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->email ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Phone:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->phone ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Address:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->address ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Province:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->province ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('City:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->city ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('District:', 'wp-customer'); ?></th>
            <td><?php echo esc_html($company->district ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Status:', 'wp-customer'); ?></th>
            <td>
                <?php
                $status_class = $company->status === 'active' ? 'status-active' : 'status-inactive';
                $status_text = $company->status === 'active' ? __('Active', 'wp-customer') : __('Inactive', 'wp-customer');
                ?>
                <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
            </td>
        </tr>
    </table>
</div>
