<?php
/**
 * Companies List View
 *
 * Displays companies (branches) in a DataTable format
 * Uses wp-app-core DataTable system
 *
 * @package WPCustomer
 * @subpackage Views\Companies
 * @since 1.1.0
 * @author arisciwek
 */

defined('ABSPATH') || exit;

// Check permission
use WPCustomer\Validators\Companies\CompaniesValidator;
$validator = new CompaniesValidator();

if (!$validator->can_access_page()) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-customer'));
}

$can_create = $validator->can_create_company();
?>

<div class="wrap wpapp-dashboard-wrap">
<div class="wrap wpapp-datatable-page">
    <!-- Page Header -->
    <div class="wpapp-page-header">
        <div class="wpapp-page-header-container">
            <div class="header-left">
                <h1 class="wp-heading-inline">
                    <?php echo esc_html__('Perusahaan-2', 'wp-customer'); ?>
                </h1>
                <div class="subtitle">
                    <?php echo esc_html__('Branches Management with Hook System', 'wp-customer'); ?>
                </div>
            </div>

            <?php if ($can_create) : ?>
                <div class="header-right">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-customer-companies&action=create')); ?>"
                       class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php echo esc_html__('Add New Company', 'wp-customer'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="wp-header-end">

    <?php
    /**
     * Action: Before companies list table
     *
     * @since 1.1.0
     */
    do_action('wp_customer_before_companies_list');
    ?>

    <!-- Statistics Container -->
    <div class="wpapp-statistics-container">
        <div class="customer-statistics-cards hidden" id="companies-statistics">
            <div class="customer-stats-card">
                <div class="stats-icon">
                    <span class="dashicons dashicons-building"></span>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number" id="stat-total">0</h3>
                    <p class="stats-label"><?php echo esc_html__('Total Companies', 'wp-customer'); ?></p>
                </div>
            </div>

            <div class="customer-stats-card">
                <div class="stats-icon active">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number" id="stat-active">0</h3>
                    <p class="stats-label"><?php echo esc_html__('Active', 'wp-customer'); ?></p>
                </div>
            </div>

            <div class="customer-stats-card">
                <div class="stats-icon pusat">
                    <span class="dashicons dashicons-admin-home"></span>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number" id="stat-pusat">0</h3>
                    <p class="stats-label"><?php echo esc_html__('Headquarters', 'wp-customer'); ?></p>
                </div>
            </div>

            <div class="customer-stats-card">
                <div class="stats-icon cabang">
                    <span class="dashicons dashicons-store"></span>
                </div>
                <div class="stats-content">
                    <h3 class="stats-number" id="stat-cabang">0</h3>
                    <p class="stats-label"><?php echo esc_html__('Branches', 'wp-customer'); ?></p>
                </div>
            </div>
        </div>
        <!-- End customer-statistics-cards -->
    </div>
    <!-- End wpapp-statistics-container -->

    <!-- Filters Container -->
    <?php if (current_user_can('edit_all_customers')) : ?>
    <div class="wpapp-filters-container">
        <div class="wpapp-datatable-filters">
            <label for="status-filter">
                <?php echo esc_html__('Filter Status:', 'wp-customer'); ?>
            </label>
            <select id="status-filter" class="status-filter">
                <option value="active" selected><?php echo esc_html__('Active', 'wp-customer'); ?></option>
                <option value="inactive"><?php echo esc_html__('Inactive', 'wp-customer'); ?></option>
                <option value=""><?php echo esc_html__('All Status', 'wp-customer'); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>
    <!-- End wpapp-filters-container -->

    <!-- DataTable Layout Container -->
    <div class="wpapp-datatable-layout">

        <!-- Sliding Panel Row Container -->
        <div class="row" id="wpapp-datatable-container">

        <!-- Left Panel: DataTable -->
        <div class="col-md-12" id="wpapp-companies-table-container">

            <div class="wpapp-companies-list-container">
                <!-- DataTable -->
        <table id="wpapp-companies-datatable" class="display wp-customer-datatable">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Code', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Company Name', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Disnaker', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Contact', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Address', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-customer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTable will populate this -->
            </tbody>
            <tfoot>
                <tr>
                    <th><?php echo esc_html__('ID', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Code', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Company Name', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Disnaker', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Contact', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Address', 'wp-customer'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-customer'); ?></th>
                </tr>
            </tfoot>
        </table>

            </div>
            <!-- End wpapp-companies-list-container -->

        </div>
        <!-- End Left Panel -->

        <!-- Right Panel: Company Detail (sliding panel) -->
        <div class="col-md-5 company-detail-panel hidden" id="company-detail-panel">
            <div id="company-detail-content">
                <!-- Content akan di-load via AJAX -->
                <div class="loading-placeholder">
                    <span class="spinner is-active"></span>
                    <p>Loading company details...</p>
                </div>
            </div>
        </div>
        <!-- End Right Panel -->

        </div>
        <!-- End wpapp-datatable-container -->

    </div>
    <!-- End wpapp-datatable-layout -->

    <?php
    /**
     * Action: After companies list table
     *
     * @since 1.1.0
     */
    do_action('wp_customer_after_companies_list');
    ?>
</div>
</div>