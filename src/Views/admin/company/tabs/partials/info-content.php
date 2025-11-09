<?php
/**
 * Company Info Tab Content (Lazy-loaded)
 *
 * @package     WP_Customer
 * @subpackage  Views/Admin/Company/Tabs/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/admin/company/tabs/partials/info-content.php
 *
 * Description: Actual content untuk Info tab.
 *              Di-load via AJAX oleh handle_load_info_tab()
 *              Menampilkan detail informasi company (branch)
 *
 * Changelog:
 * 1.0.0 - 2025-11-09 (TODO-2195)
 * - Initial implementation
 * - Display company details
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-tab-content-wrapper">
    <div class="company-info-grid">
        <div class="info-group">
            <label><?php esc_html_e('Company Code:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->code ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Company Name:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->name ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Type:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php
                $type_display = isset($company->type) && $company->type === 'pusat'
                    ? __('Head Office', 'wp-customer')
                    : __('Branch', 'wp-customer');
                echo esc_html($type_display);
                ?>
            </div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Email:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php if (!empty($company->email)): ?>
                    <a href="mailto:<?php echo esc_attr($company->email); ?>">
                        <?php echo esc_html($company->email); ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Phone:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php if (!empty($company->phone)): ?>
                    <a href="tel:<?php echo esc_attr($company->phone); ?>">
                        <?php echo esc_html($company->phone); ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Address:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->address ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Province:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->province ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('City:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->city ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Postal Code:', 'wp-customer'); ?></label>
            <div class="info-value"><?php echo esc_html($company->postal_code ?? '-'); ?></div>
        </div>

        <div class="info-group">
            <label><?php esc_html_e('Status:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php
                $status_class = isset($company->status) && $company->status === 'active'
                    ? 'status-active'
                    : 'status-inactive';
                $status_text = isset($company->status) && $company->status === 'active'
                    ? __('Active', 'wp-customer')
                    : __('Inactive', 'wp-customer');
                ?>
                <span class="status-badge <?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($status_text); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($company->created_at)): ?>
        <div class="info-group">
            <label><?php esc_html_e('Created At:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($company->created_at))); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($company->updated_at)): ?>
        <div class="info-group">
            <label><?php esc_html_e('Updated At:', 'wp-customer'); ?></label>
            <div class="info-value">
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($company->updated_at))); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.company-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.info-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-group label {
    font-weight: 600;
    color: #555;
    font-size: 12px;
    text-transform: uppercase;
}

.info-group .info-value {
    font-size: 14px;
    color: #333;
}

.info-group .info-value a {
    color: #2271b1;
    text-decoration: none;
}

.info-group .info-value a:hover {
    text-decoration: underline;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
