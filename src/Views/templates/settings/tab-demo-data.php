<?php
/**
 * Demo Data Generator Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     2.2.1
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-demo-data.php
 *
 * Description: Demo data management using shared assets from wp-app-core.
 *              Uses generic demo-data-button pattern with data attributes.
 *              Assets loaded: wpapp-demo-data.css, wpapp-demo-data.js
 *              Development Settings form uses sticky footer for consistency.
 *              Card-footer pattern ensures consistent button alignment.
 *              Dependency checking enabled via data-requires attributes (TODO-1209).
 *
 * Changelog:
 * 2.2.1 - 2025-01-13 (TODO-1209)
 * - Added: Dependency checking attributes (data-requires, data-check-action, data-check-nonce)
 * - Added: 6 buttons now have dependency validation
 * - Improved: Buttons auto-disabled until dependencies met
 * - Improved: Clear tooltip feedback for dependency status
 * 2.2.0 - 2025-01-13
 * - Fixed: Button alignment with card-footer wrapper
 * - Added: .demo-data-card-footer wrapper around all buttons
 * - Improved: Consistent button positioning across all cards
 * 2.1.0 - 2025-01-13
 * - Fixed: Sticky footer now shows for Development Settings form
 * - Added: Form ID (wp-customer-demo-data-form) to Development Settings
 * - Removed: Internal submit button (uses sticky footer button instead)
 * - Removed: Filter that hides sticky footer
 * 2.0.0 - 2025-01-13 (TODO-2201)
 * - BREAKING: Updated to use shared assets from wp-app-core
 * - Changed: Button class from customer-generate-demo-data to demo-data-button
 * - Changed: data-type to data-action for AJAX actions
 * - Added: data-confirm for WPModal confirmations
 * - Removed: data-requires, data-check-nonce (handled in backend)
 * 1.0.11 - Previous version
 * - Old pattern with local assets
 */

if (!defined('ABSPATH')) {
    die;
}

// Verify nonce and capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>

<div class="wrap">
    <div id="demo-data-messages"></div>

    <div class="demo-data-section">
        <h3><?php _e('Generate Demo Data', 'wp-customer'); ?></h3>
        <p class="description">
            <?php _e('Generate demo data for testing purposes. Each button will generate specific type of data.', 'wp-customer'); ?>
        </p>

        <div class="demo-data-grid">
            <!-- Feature Groups -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Feature Groups', 'wp-customer'); ?></h4>
                <p><?php _e('Generate feature group definitions for membership capabilities.', 'wp-customer'); ?></p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_membership_groups"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_membership_groups'); ?>"
                            data-confirm="<?php esc_attr_e('Generate membership feature groups?', 'wp-customer'); ?>">
                        <?php _e('Generate Feature Groups', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Membership Features -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Features', 'wp-customer'); ?></h4>
                <p><?php _e('Generate membership feature definitions for capabilities and limits.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires feature groups to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_membership_features"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_membership_features'); ?>"
                            data-confirm="<?php esc_attr_e('Generate membership features?', 'wp-customer'); ?>"
                            data-requires="membership-groups"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Membership Features', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Membership Levels -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Levels', 'wp-customer'); ?></h4>
                <p><?php _e('Generate default membership levels configuration.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires membership features to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_membership_levels"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_membership_levels'); ?>"
                            data-confirm="<?php esc_attr_e('Generate membership levels?', 'wp-customer'); ?>"
                            data-requires="membership-features"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Membership Levels', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Customers -->
            <div class="demo-data-card">
                <h4><?php _e('Customers', 'wp-customer'); ?></h4>
                <p><?php _e('Generate sample customer data with WordPress users.', 'wp-customer'); ?></p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_customers"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_customers'); ?>"
                            data-confirm="<?php esc_attr_e('Generate customer demo data?', 'wp-customer'); ?>">
                        <?php _e('Generate Customers', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Branches -->
            <div class="demo-data-card">
                <h4><?php _e('Branches', 'wp-customer'); ?></h4>
                <p><?php _e('Generate branch offices for existing customers.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires customers to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_branches"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_branches'); ?>"
                            data-confirm="<?php esc_attr_e('Generate branch demo data?', 'wp-customer'); ?>"
                            data-requires="customer"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Branches', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Employees -->
            <div class="demo-data-card">
                <h4><?php _e('Employees', 'wp-customer'); ?></h4>
                <p><?php _e('Generate employee data for branches.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires customers and branches to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_employees"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_employees'); ?>"
                            data-confirm="<?php esc_attr_e('Generate employee demo data?', 'wp-customer'); ?>"
                            data-requires="customer,branch"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Employees', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Customer Memberships -->
            <div class="demo-data-card">
                <h4><?php _e('Customer Memberships', 'wp-customer'); ?></h4>
                <p><?php _e('Generate membership data for existing branches and customers.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires membership groups, features, levels, and branches to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_memberships"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_memberships'); ?>"
                            data-confirm="<?php esc_attr_e('Generate customer memberships?', 'wp-customer'); ?>"
                            data-requires="membership-levels"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Customer Memberships', 'wp-customer'); ?>
                    </button>
                </div>
            </div>

            <!-- Company Invoices -->
            <div class="demo-data-card">
                <h4><?php _e('Company Invoices', 'wp-customer'); ?></h4>
                <p><?php _e('Generate invoice data for branches with membership upgrades and payments.', 'wp-customer'); ?></p>
                <p class="description" style="color: #646970; font-size: 12px;">
                    <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                    <?php _e('Requires memberships to be generated first.', 'wp-customer'); ?>
                </p>
                <div class="demo-data-card-footer">
                    <button type="button"
                            class="button button-primary demo-data-button"
                            data-action="customer_generate_invoices"
                            data-nonce="<?php echo wp_create_nonce('customer_generate_invoices'); ?>"
                            data-confirm="<?php esc_attr_e('Generate company invoices?', 'wp-customer'); ?>"
                            data-requires="memberships"
                            data-check-action="customer_check_demo_data"
                            data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
                        <?php _e('Generate Company Invoices', 'wp-customer'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Development Settings Section -->
    <div class="development-settings-section" style="margin-top: 30px;">
        <h3><?php _e('Development Settings', 'wp-customer'); ?></h3>
        <form method="post" action="options.php" id="wp-customer-demo-data-form">
            <?php
            settings_fields('wp_customer_development_settings');
            $dev_settings = get_option('wp_customer_development_settings', array(
                'enable_development' => 0,
                'clear_data_on_deactivate' => 0
            ));
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Development Mode', 'wp-customer'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_development_settings[enable_development]"
                                   value="1"
                                   <?php checked($dev_settings['enable_development'], 1); ?>>
                            <?php _e('Enable development mode', 'wp-customer'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, this overrides WP_CUSTOMER_DEVELOPMENT constant.', 'wp-customer'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Data Cleanup', 'wp-customer'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_development_settings[clear_data_on_deactivate]"
                                   value="1"
                                   <?php checked($dev_settings['clear_data_on_deactivate'], 1); ?>>
                            <?php _e('Clear demo data on plugin deactivation', 'wp-customer'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: When enabled, all demo data will be permanently deleted when the plugin is deactivated.', 'wp-customer'); ?>
                        </p>
                        <p class="description">
                            <strong><?php _e('Note:', 'wp-customer'); ?></strong>
                            <?php _e('Both Development Mode and this option must be checked for data to be cleared on deactivation.', 'wp-customer'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </form>
    </div>

</div>
