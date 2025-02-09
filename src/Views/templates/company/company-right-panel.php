<?php

/**
 * Company Right Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/company/company-right-panel.php
 */

defined('ABSPATH') || exit;
?>

<div class="wp-company-panel-header">
    <h2>Detail Perusahaan: <span id="company-header-name"></span></h2>
    <button type="button" class="wp-company-close-panel">×</button>
</div>

<div class="wp-company-panel-content">
    <div class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" data-tab="company-details">Data Perusahaan</a>
        <a href="#" class="nav-tab" data-tab="membership-info">Membership</a>
    </div>

    <?php
    // Include tab contents
    foreach ([
        'company/partials/_company_details.php',
        'company/partials/_company_membership.php'
    ] as $template) {
        include_once WP_CUSTOMER_PATH . 'src/Views/templates/' . $template;
    }
    ?>
</div>
