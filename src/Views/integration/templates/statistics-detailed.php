<?php
/**
 * Generic Template: Detailed Statistics
 *
 * Displays detailed customer and branch statistics for any entity.
 * Includes additional information and visual enhancements.
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

// Calculate averages
$avg_branches_per_customer = $customer_count > 0 ? round($branch_count / $customer_count, 2) : 0;
?>

<div class="wpapp-detail-section wp-customer-integration wp-customer-statistics-detailed">
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

    <!-- Primary Statistics -->
    <div class="wp-customer-stats-grid">
        <div class="wp-customer-stat-box">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html(number_format_i18n($customer_count)); ?></div>
                <div class="stat-label"><?php esc_html_e('Total Customer', 'wp-customer'); ?></div>
            </div>
        </div>

        <div class="wp-customer-stat-box">
            <div class="stat-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html(number_format_i18n($branch_count)); ?></div>
                <div class="stat-label"><?php esc_html_e('Total Branch', 'wp-customer'); ?></div>
            </div>
        </div>

        <?php if ($customer_count > 0): ?>
        <div class="wp-customer-stat-box">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($avg_branches_per_customer); ?></div>
                <div class="stat-label"><?php esc_html_e('Avg. Branch/Customer', 'wp-customer'); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Additional Information -->
    <?php if ($customer_count > 0 || $branch_count > 0): ?>
    <div class="wpapp-detail-row wp-customer-stat-summary">
        <label><?php esc_html_e('Summary', 'wp-customer'); ?>:</label>
        <span class="description">
            <?php
            if ($customer_count > 0 && $branch_count > 0) {
                printf(
                    esc_html__('%1$s memiliki %2$d customer dengan total %3$d branch.', 'wp-customer'),
                    '<strong>' . esc_html(ucfirst($entity_type)) . '</strong>',
                    $customer_count,
                    $branch_count
                );
            } elseif ($customer_count > 0) {
                printf(
                    esc_html__('%1$s memiliki %2$d customer.', 'wp-customer'),
                    '<strong>' . esc_html(ucfirst($entity_type)) . '</strong>',
                    $customer_count
                );
            } elseif ($branch_count > 0) {
                printf(
                    esc_html__('%1$s memiliki %2$d branch.', 'wp-customer'),
                    '<strong>' . esc_html(ucfirst($entity_type)) . '</strong>',
                    $branch_count
                );
            }
            ?>
        </span>
    </div>
    <?php endif; ?>

    <?php if ($customer_count === 0 && $branch_count === 0): ?>
    <div class="wpapp-detail-row wp-customer-stat-empty">
        <label></label>
        <span class="description">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('Belum ada customer yang terhubung dengan entity ini.', 'wp-customer'); ?>
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

<style>
.wp-customer-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.wp-customer-stat-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
    border-left: 3px solid #2271b1;
}

.wp-customer-stat-box .stat-icon {
    font-size: 24px;
    color: #2271b1;
}

.wp-customer-stat-box .stat-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.wp-customer-stat-box .stat-content {
    flex: 1;
}

.wp-customer-stat-box .stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3338;
    line-height: 1.2;
}

.wp-customer-stat-box .stat-label {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
}

.wp-customer-stat-summary {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dcdcde;
}

.wp-customer-stat-empty {
    padding: 15px;
    background: #f0f0f1;
    border-radius: 4px;
}

.wp-customer-stat-empty .dashicons {
    color: #646970;
}
</style>
