<?php

/**
 * Company Right Panel Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates
 * @version     1.0.11
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
        <?php
        // Get registered tabs
        $tabs = apply_filters('wp_company_detail_tabs', [
            'company-details' => [
                'title' => __('Data Perusahaan', 'wp-customer'),
                'template' => 'company/partials/_company_details.php',
                'priority' => 10
            ],
            'membership-info' => [
                'title' => __('Membership', 'wp-customer'),
                'template' => 'company/partials/_company_membership.php',
                'priority' => 20
            ]
        ]);

        // Sort tabs by priority
        uasort($tabs, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // Render tab navigation
        foreach ($tabs as $tab_id => $tab) {
            $active_class = ($tab_id === 'company-details') ? 'nav-tab-active' : '';
            printf(
                '<a href="#" class="nav-tab %s" data-tab="%s">%s</a>',
                esc_attr($active_class),
                esc_attr($tab_id),
                esc_html($tab['title'])
            );
        }
        ?>
    </div>

    <?php
    // Render tab contents
    foreach ($tabs as $tab_id => $tab) {
        // Allow plugins to override template path
        $template_path = apply_filters(
            'wp_company_detail_tab_template', 
            WP_CUSTOMER_PATH . 'src/Views/templates/' . $tab['template'],
            $tab_id
        );
        
        if (file_exists($template_path)) {
            include_once $template_path;
        }
    }
    ?>
</div>
