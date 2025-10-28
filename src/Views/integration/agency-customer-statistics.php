<?php
/**
 * Agency Customer Statistics View Template
 *
 * Template for displaying customer statistics in agency detail tab.
 * Injected into wp-agency detail page via TabContentInjector.
 *
 * @package     WPCustomer
 * @subpackage  Views/Integration
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/integration/agency-customer-statistics.php
 *
 * Description: View template untuk customer statistics di agency detail.
 *              Displays customer count, branch count, dll.
 *              Menggunakan style yang consistent dengan agency detail page.
 *
 * Variables Available:
 * @var int   $customer_count Number of customers
 * @var int   $branch_count   Number of branches
 * @var array $statistics     Full statistics array (optional)
 *
 * Changelog:
 * 1.0.0 - 2025-10-28
 * - Initial implementation (TODO-2177, Task-3085)
 * - Display customer count dan branch count
 * - Consistent styling dengan agency detail page
 * - Extensibility hooks untuk custom content
 */

defined('ABSPATH') || exit;

// Default values jika tidak di-pass
$customer_count = $customer_count ?? 0;
$branch_count = $branch_count ?? 0;
$statistics = $statistics ?? [];
?>

<div class="agency-detail-section wp-customer-integration">
    <h3><?php esc_html_e('Statistik Customer', 'wp-customer'); ?></h3>

    <div class="agency-detail-row">
        <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
        <span><strong><?php echo esc_html($customer_count); ?></strong></span>
    </div>

    <div class="agency-detail-row">
        <label><?php esc_html_e('Total Cabang', 'wp-customer'); ?>:</label>
        <span><strong><?php echo esc_html($branch_count); ?></strong></span>
    </div>

    <?php if ($customer_count > 0): ?>
    <div class="agency-detail-row">
        <label><?php esc_html_e('Keterangan', 'wp-customer'); ?>:</label>
        <span><?php
            printf(
                esc_html__('%d customer terhubung dengan agency ini melalui %d cabang', 'wp-customer'),
                $customer_count,
                $branch_count
            );
        ?></span>
    </div>
    <?php else: ?>
    <div class="agency-detail-row">
        <label><?php esc_html_e('Keterangan', 'wp-customer'); ?>:</label>
        <span class="text-muted"><?php esc_html_e('Belum ada customer yang terhubung dengan agency ini', 'wp-customer'); ?></span>
    </div>
    <?php endif; ?>

    <?php
    /**
     * Action: After customer statistics content
     *
     * Allows other plugins/themes to add custom content after statistics.
     *
     * @param int   $customer_count Customer count
     * @param int   $branch_count   Branch count
     * @param array $statistics     Full statistics array
     *
     * @since 1.0.0
     */
    do_action('wp_customer_after_agency_statistics', $customer_count, $branch_count, $statistics);
    ?>
</div>

<style>
    .wp-customer-integration {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .wp-customer-integration h3 {
        color: #2271b1;
        margin-bottom: 15px;
        font-size: 14px;
        font-weight: 600;
    }

    .wp-customer-integration .agency-detail-row {
        margin-bottom: 10px;
    }

    .wp-customer-integration .text-muted {
        color: #999;
        font-style: italic;
    }
</style>
