<?php
/**
 * Company Info Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/tabs/info.php
 *
 * Description: Info tab untuk company detail panel.
 *              Uses lazy-load pattern dengan wpdt-tab-autoload class.
 *              Content dimuat via AJAX saat tab pertama kali diklik.
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation
 * - Lazy-load pattern
 * - AJAX endpoint: load_company_info_tab
 */

defined('ABSPATH') || exit;

// $data is passed from controller (company object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Company data not available', 'wp-customer') . '</p>';
    return;
}

$company = $data;
?>

<?php
// Direct include content (no lazy-load for first tab)
include WP_CUSTOMER_PATH . 'src/Views/admin/company/tabs/partials/info-content.php';
?>
