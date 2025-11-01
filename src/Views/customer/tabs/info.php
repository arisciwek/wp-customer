<?php
/**
 * Customer Info Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/tabs/info.php
 *
 * Description: Tab untuk menampilkan informasi customer.
 *              Shows customer details, contact info, and address.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following platform-staff pattern
 */

defined('ABSPATH') || exit;

// $data is passed from controller (customer object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Customer data not available', 'wp-customer') . '</p>';
    return;
}

$customer = $data;
?>

<div class="customer-info-tab">
    <div class="customer-info-section">
        <h3><?php echo esc_html__('Customer Details', 'wp-customer'); ?></h3>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Code:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->code ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Name:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->name ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('NPWP:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->npwp ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('NIB:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->nib ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Email:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->email ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Phone:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->phone ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Status:', 'wp-customer'); ?></label>
            <span class="customer-status-badge customer-status-<?php echo esc_attr($customer->status ?? 'aktif'); ?>">
                <?php echo esc_html($customer->status ?? 'aktif'); ?>
            </span>
        </div>
    </div>

    <?php if (isset($customer->pusat_name)): ?>
    <div class="customer-info-section">
        <h3><?php echo esc_html__('Head Office', 'wp-customer'); ?></h3>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Name:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->pusat_name ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Code:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->pusat_code ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Address:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->pusat_address ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Province:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->province_name ?? '-'); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Regency:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->regency_name ?? '-'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="customer-info-section">
        <h3><?php echo esc_html__('Statistics', 'wp-customer'); ?></h3>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Branches:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->branch_count ?? 0); ?></span>
        </div>

        <div class="customer-info-row">
            <label><?php echo esc_html__('Employees:', 'wp-customer'); ?></label>
            <span><?php echo esc_html($customer->employee_count ?? 0); ?></span>
        </div>
    </div>
</div>
