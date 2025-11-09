<?php
/**
 * Company Staff Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/tabs/staff.php
 *
 * Description: Staff tab untuk company detail panel.
 *              Uses lazy-load pattern dengan wpdt-tab-autoload class.
 *              Content dimuat via AJAX saat tab pertama kali diklik.
 *              Menampilkan DataTable employee yang bekerja di company ini.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation
 * - Lazy-load pattern
 * - AJAX endpoint: load_company_staff_tab
 */

defined('ABSPATH') || exit;

// $data is passed from controller (company object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Company data not available', 'wp-customer') . '</p>';
    return;
}

$company = $data;
$company_id = $company->id ?? 0;

if (!$company_id) {
    echo '<p>' . __('Company ID not available', 'wp-customer') . '</p>';
    return;
}
?>

<div class="wpdt-company-staff-tab wpdt-tab-autoload"
     data-company-id="<?php echo esc_attr($company_id); ?>"
     data-load-action="load_company_staff_tab"
     data-content-target=".wpdt-company-staff-content"
     data-error-message="<?php esc_attr_e('Failed to load staff data', 'wp-customer'); ?>">

    <div class="wpdt-tab-loading">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading staff data...', 'wp-customer'); ?></p>
    </div>

    <div class="wpdt-company-staff-content wpdt-tab-loaded-content">
        <!-- Content will be loaded via AJAX -->
    </div>

    <div class="wpdt-tab-error">
        <p class="wpdt-error-message"></p>
    </div>
</div>
