<?php
/**
 * Generic Template: Simple Statistics
 *
 * Displays basic customer and branch statistics for any entity.
 *
 * Variables available:
 * - $entity_type   : string - Entity type identifier
 * - $entity_data   : object - Entity data
 * - $statistics    : array  - Statistics data
 *   - customer_count : int - Number of customers
 *   - branch_count   : int - Number of branches
 * - $label         : string - Section label
 * - $config        : array  - Injection configuration
 *
 * @package WPCustomer
 * @subpackage Views/Integration/Templates
 * @since 1.0.12
 */

defined('ABSPATH') || exit;

// Extract statistics
$customer_count = $statistics['customer_count'] ?? 0;
$branch_count = $statistics['branch_count'] ?? 0;
?>

<div class="wpapp-detail-section wp-customer-integration wp-customer-statistics-simple">
    <?php
    /**
     * Action: Before statistics section
     *
     * @param string $entity_type Entity type
     * @param object $entity_data Entity data
     * @param array  $statistics  Statistics data
     *
     * @since 1.0.12
     */
    do_action('wp_customer_before_statistics_section', $entity_type, $entity_data, $statistics);
    ?>

    <h3><?php echo esc_html($label); ?></h3>

    <div class="wpapp-detail-row">
        <label><?php esc_html_e('Total Customer', 'wp-customer'); ?>:</label>
        <span class="wp-customer-stat-value">
            <strong><?php echo esc_html(number_format_i18n($customer_count)); ?></strong>
            <?php if ($customer_count > 0): ?>
                <span class="wp-customer-stat-unit"><?php echo esc_html(_n('customer', 'customers', $customer_count, 'wp-customer')); ?></span>
            <?php endif; ?>
        </span>
    </div>

    <div class="wpapp-detail-row">
        <label><?php esc_html_e('Total Branch', 'wp-customer'); ?>:</label>
        <span class="wp-customer-stat-value">
            <strong><?php echo esc_html(number_format_i18n($branch_count)); ?></strong>
            <?php if ($branch_count > 0): ?>
                <span class="wp-customer-stat-unit"><?php echo esc_html(_n('branch', 'branches', $branch_count, 'wp-customer')); ?></span>
            <?php endif; ?>
        </span>
    </div>

    <?php if ($customer_count > 0 || $branch_count > 0): ?>
    <div class="wpapp-detail-row wp-customer-stat-note">
        <label></label>
        <span class="description">
            <?php
            printf(
                esc_html__('Data terhubung dengan %s ini', 'wp-customer'),
                esc_html($entity_type)
            );
            ?>
        </span>
    </div>
    <?php endif; ?>

    <?php
    /**
     * Action: After statistics rows
     *
     * @param string $entity_type Entity type
     * @param object $entity_data Entity data
     * @param array  $statistics  Statistics data
     *
     * @since 1.0.12
     */
    do_action('wp_customer_after_statistics_rows', $entity_type, $entity_data, $statistics);
    ?>

    <?php
    /**
     * Action: After statistics section
     *
     * @param string $entity_type Entity type
     * @param object $entity_data Entity data
     * @param array  $statistics  Statistics data
     *
     * @since 1.0.12
     */
    do_action('wp_customer_after_statistics_section', $entity_type, $entity_data, $statistics);
    ?>
</div>
