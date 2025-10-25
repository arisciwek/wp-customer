<?php
/**
 * Company Detail View (Sliding Panel)
 *
 * Displays company (branch) details in a sliding panel
 * Includes tabs: Detail, Employees (lazy loaded)
 *
 * @package WPCustomer
 * @subpackage Views\Companies
 * @since 1.1.0
 * @author arisciwek
 *
 * Variables available:
 * @var object $company   Branch data
 * @var object $customer  Customer data
 */

defined('ABSPATH') || exit;

if (!isset($company) || !isset($customer)) {
    echo '<div class="notice notice-error"><p>Invalid company data</p></div>';
    return;
}
?>

<!-- Close Button -->
<div class="detail-panel-header">
    <button type="button" class="button-link close-detail-panel" id="close-company-detail">
        <span class="dashicons dashicons-no-alt"></span>
        Close
    </button>
</div>

<!-- Company Title -->
<div class="detail-panel-title">
    <h2><?php echo esc_html($company->name); ?></h2>
    <p class="company-code"><?php echo esc_html($company->code); ?></p>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs company-detail-tabs" role="tablist">
    <li class="active">
        <a href="#tab-detail" data-tab="detail" role="tab" aria-selected="true">
            <span class="dashicons dashicons-info"></span>
            Detail
        </a>
    </li>
    <li>
        <a href="#tab-employees" data-tab="employees" role="tab" aria-selected="false">
            <span class="dashicons dashicons-groups"></span>
            Employees
        </a>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content company-detail-content">

    <!-- Tab: Detail (Loaded immediately) -->
    <div id="tab-detail" class="tab-pane active" role="tabpanel">

        <!-- Branch Information -->
        <div class="detail-section">
            <h3 class="section-title">
                <span class="dashicons dashicons-building"></span>
                Branch Information
            </h3>

            <table class="detail-table">
                <tr>
                    <th>Code</th>
                    <td><?php echo esc_html($company->code); ?></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td><?php echo esc_html($company->name); ?></td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>
                        <span class="badge badge-<?php echo $company->type === 'pusat' ? 'primary' : 'secondary'; ?>">
                            <?php echo esc_html(ucfirst($company->type)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge badge-<?php echo $company->status === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo esc_html(ucfirst($company->status)); ?>
                        </span>
                    </td>
                </tr>
                <?php if ($company->nitku) : ?>
                <tr>
                    <th>NITKU</th>
                    <td><?php echo esc_html($company->nitku); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($company->phone) : ?>
                <tr>
                    <th>Phone</th>
                    <td>
                        <span class="dashicons dashicons-phone"></span>
                        <?php echo esc_html($company->phone); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($company->email) : ?>
                <tr>
                    <th>Email</th>
                    <td>
                        <span class="dashicons dashicons-email"></span>
                        <a href="mailto:<?php echo esc_attr($company->email); ?>">
                            <?php echo esc_html($company->email); ?>
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($company->address) : ?>
                <tr>
                    <th>Address</th>
                    <td><?php echo esc_html($company->address); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($company->postal_code) : ?>
                <tr>
                    <th>Postal Code</th>
                    <td><?php echo esc_html($company->postal_code); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Customer Information -->
        <div class="detail-section">
            <h3 class="section-title">
                <span class="dashicons dashicons-businessperson"></span>
                Customer Information
            </h3>

            <table class="detail-table">
                <tr>
                    <th>Customer Code</th>
                    <td><?php echo esc_html($customer->code); ?></td>
                </tr>
                <tr>
                    <th>Customer Name</th>
                    <td><?php echo esc_html($customer->name); ?></td>
                </tr>
                <?php if ($customer->npwp) : ?>
                <tr>
                    <th>NPWP</th>
                    <td><?php echo esc_html($customer->npwp); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($customer->nib) : ?>
                <tr>
                    <th>NIB</th>
                    <td><?php echo esc_html($customer->nib); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Customer Status</th>
                    <td>
                        <span class="badge badge-<?php echo $customer->status === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo esc_html(ucfirst($customer->status)); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Metadata -->
        <div class="detail-section">
            <h3 class="section-title">
                <span class="dashicons dashicons-calendar-alt"></span>
                Metadata
            </h3>

            <table class="detail-table">
                <tr>
                    <th>Created At</th>
                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($company->created_at))); ?></td>
                </tr>
                <?php if ($company->updated_at) : ?>
                <tr>
                    <th>Updated At</th>
                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($company->updated_at))); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

    </div>
    <!-- End Tab: Detail -->

    <!-- Tab: Employees (Lazy loaded) -->
    <div id="tab-employees" class="tab-pane" role="tabpanel">
        <div class="tab-loading">
            <span class="spinner is-active"></span>
            <p>Loading employees data...</p>
        </div>
        <div class="tab-content-placeholder">
            <!-- Content akan di-load via AJAX saat tab di-click -->
            <p><em>Employees DataTable akan dikerjakan di tahap berikutnya.</em></p>
        </div>
    </div>
    <!-- End Tab: Employees -->

</div>
<!-- End Tabs Content -->
