<?php
/**
 * Customer Dashboard - Header Title
 *
 * @package     WP_Customer
 * @subpackage  Views/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/customer/partials/header-title.php
 *
 * Description: Page header title untuk customer dashboard.
 *              Renders title and subtitle.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-2187)
 * - Initial implementation following platform-staff pattern
 */

defined('ABSPATH') || exit;
?>

<h1 class="customer-title"><?php echo esc_html__('WP Customer', 'wp-customer'); ?></h1>
<div class="customer-subtitle"><?php echo esc_html__('Manage customers and their data', 'wp-customer'); ?></div>
